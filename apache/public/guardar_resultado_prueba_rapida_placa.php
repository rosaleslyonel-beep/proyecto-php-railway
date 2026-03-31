<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['usuario'])) {
    echo "Error: Usuario no autenticado.";
    exit;
}

$id_resultado = $_POST['id_resultado'] ?? null;
$id_muestra = $_POST['id_muestra'] ?? null;
$id_analisis = $_POST['id_analisis'] ?? null;
$id_protocolo = $_POST['id_protocolo'] ?? null;

if (!$id_muestra || !$id_analisis || !$id_protocolo) {
    echo "Error: Faltan datos del formulario.";
    exit;
}

$id_usuario = $_SESSION['usuario']['id_usuario'] ?? null;

if ($id_resultado) {
    $stmt = $conexion->prepare("SELECT resultados_incluidos_json FROM protocolo_emisiones_resultados WHERE id_protocolo = ?");
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

$datos = [
    'lote_antigeno_antisuero' => $_POST['lote_antigeno_antisuero'] ?? '',
    'fecha' => $_POST['fecha'] ?? '',
    'responsable' => $_POST['responsable'] ?? '',
    'supervisor' => $_POST['supervisor'] ?? '',
    'otra_nombre' => $_POST['otra_nombre'] ?? '',
    'filas' => $_POST['filas'] ?? [],
];

try {
    if ($id_resultado) {
        $stmt = $conexion->prepare("UPDATE resultados_analisis SET datos_json = :datos_json, observaciones = :observaciones, updated_by = :updated_by, updated_date = CURRENT_TIMESTAMP WHERE id_resultado = :id_resultado");
        $stmt->execute([
            ':datos_json' => json_encode($datos),
            ':observaciones' => $observaciones,
            ':updated_by' => $id_usuario,
            ':id_resultado' => $id_resultado
        ]);
    } else {
        $stmt = $conexion->prepare("INSERT INTO resultados_analisis (id_muestra, id_analisis, datos_json, observaciones, created_by, created_date) VALUES (:id_muestra, :id_analisis, :datos_json, :observaciones, :created_by, CURRENT_TIMESTAMP)");
        $stmt->execute([
            ':id_muestra' => $id_muestra,
            ':id_analisis' => $id_analisis,
            ':datos_json' => json_encode($datos),
            ':observaciones' => $observaciones,
            ':created_by' => $id_usuario
        ]);
    }

    header("Location: gestion_protocolos.php?id={$id_protocolo}&tab=tab_resultados");
    exit;
} catch (PDOException $e) {
    echo "Error al guardar: " . $e->getMessage();
}
?>
