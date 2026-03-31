<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['usuario'])) {
    echo "Error: Usuario no autenticado.";
    exit;
}

$id_protocolo = $_POST['id_protocolo'] ?? null;
$id_analisis = $_POST['id_analisis'] ?? null;
$muestras = $_POST['muestras'] ?? [];
$observaciones = $_POST['observaciones'] ?? '';

if (!$id_protocolo || !$id_analisis || !is_array($muestras) || count($muestras) === 0) {
    echo "Error: Faltan datos del formulario.";
    exit;
}

$id_usuario = $_SESSION['usuario']['id_usuario'] ?? null;

$datos_base = [
    'fecha' => $_POST['fecha'] ?? '',
    'lote_antigeno' => $_POST['lote_antigeno'] ?? '',
    'lote_antisuero' => $_POST['lote_antisuero'] ?? '',
    'responsable' => $_POST['responsable'] ?? '',
    'supervisor' => $_POST['supervisor'] ?? '',
];

try {
    $conexion->beginTransaction();

    foreach ($muestras as $fila) {
        $id_muestra = (int)($fila['id_muestra'] ?? 0);
        $resultado_individual = $fila['resultado'] ?? '';

        if ($id_muestra <= 0 || !in_array($resultado_individual, ['positivo', 'negativo'], true)) {
            continue;
        }

        $stmt = $conexion->prepare("
            SELECT id_resultado
            FROM resultados_analisis
            WHERE id_muestra = ? AND id_analisis = ?
        ");
        $stmt->execute([$id_muestra, $id_analisis]);
        $id_resultado = $stmt->fetchColumn();

        if ($id_resultado) {
            $stmt = $conexion->prepare("
                SELECT resultados_incluidos_json
                FROM protocolo_emisiones_resultados
                WHERE id_protocolo = ?
            ");
            $stmt->execute([$id_protocolo]);
            $emisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($emisiones as $emision) {
                $ids = json_decode($emision['resultados_incluidos_json'] ?? '[]', true);
                if (is_array($ids) && in_array((int)$id_resultado, array_map('intval', $ids), true)) {
                    header("Location: resultado_analisis.php?id_protocolo=$id_protocolo&id_muestra=$id_muestra&id_analisis=$id_analisis&error=" . urlencode("Este resultado ya fue emitido y no puede modificarse directamente. Debe generar una corrección."));
                    exit;
                }
            }
        }

        $datos = $datos_base;
        $datos['resultado'] = $resultado_individual;

        if ($id_resultado) {
            $stmt = $conexion->prepare("
                UPDATE resultados_analisis
                SET datos_json = :datos_json,
                    observaciones = :observaciones,
                    updated_by = :updated_by,
                    updated_date = CURRENT_TIMESTAMP
                WHERE id_resultado = :id_resultado
            ");
            $stmt->execute([
                ':datos_json' => json_encode($datos),
                ':observaciones' => $observaciones,
                ':updated_by' => $id_usuario,
                ':id_resultado' => $id_resultado
            ]);
        } else {
            $stmt = $conexion->prepare("
                INSERT INTO resultados_analisis (
                    id_muestra, id_analisis, datos_json, observaciones, created_by, created_date
                ) VALUES (
                    :id_muestra, :id_analisis, :datos_json, :observaciones, :created_by, CURRENT_TIMESTAMP
                )
            ");
            $stmt->execute([
                ':id_muestra' => $id_muestra,
                ':id_analisis' => $id_analisis,
                ':datos_json' => json_encode($datos),
                ':observaciones' => $observaciones,
                ':created_by' => $id_usuario
            ]);
        }
    }

    $conexion->commit();
    header("Location: gestion_protocolos.php?id={$id_protocolo}&tab=tab_resultados");
    exit;
} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    echo "Error al guardar: " . $e->getMessage();
}
?>
