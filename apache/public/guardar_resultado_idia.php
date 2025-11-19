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
$observaciones = $_POST['observaciones'] ?? '';
$placas = $_POST['placas'] ?? [];

$datos = [
    'lote_antigeno' => $_POST['lote_antigeno'] ?? '',
    'lote_agar' => $_POST['lote_agar'] ?? '',
    'fecha_elaboracion' => $_POST['fecha_elaboracion'] ?? '',
    'procesada_por' => $_POST['procesada_por'] ?? '',
    'prueba_para' => $_POST['prueba_para'] ?? '',
    'fecha_lectura' => $_POST['fecha_lectura'] ?? '',
    'realizada_por' => $_POST['realizada_por'] ?? '',
    'placas' => $placas
];

$id_usuario = $_SESSION['usuario']['id_usuario'] ?? null;

if (!$id_muestra || !$id_analisis) {
    echo "Error: Faltan datos del formulario.";
    exit;
}

try {
    if ($id_resultado) {
        $stmt = $conexion->prepare("UPDATE resultados_analisis SET 
            datos_json = :datos_json,
            observaciones = :observaciones,
            updated_by = :updated_by,
            updated_date = CURRENT_TIMESTAMP
            WHERE id_resultado = :id_resultado");

        $stmt->execute([
            ':datos_json' => json_encode($datos),
            ':observaciones' => $observaciones,
            ':updated_by' => $id_usuario,
            ':id_resultado' => $id_resultado
        ]);
    } else {
        $stmt = $conexion->prepare("INSERT INTO resultados_analisis (
            id_muestra, id_analisis, datos_json, observaciones, created_by, created_date
        ) VALUES (
            :id_muestra, :id_analisis, :datos_json, :observaciones, :created_by, CURRENT_TIMESTAMP
        )");

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
