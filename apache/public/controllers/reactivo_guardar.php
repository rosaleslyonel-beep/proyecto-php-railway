<?php
require_once "../config/helpers.php";
session_start();

$id_usuario = $_SESSION['usuario']['id_usuario'] ?? null;

// LISTAR
if (isset($_GET['listar'])) {
    $stmt = $conexion->prepare("SELECT * FROM reactivos WHERE id_analisis = ? ORDER BY orden_pipeteo");
    $stmt->execute([$_GET['id_analisis']]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// ELIMINAR
if (isset($_POST['eliminar'])) {
    $stmt = $conexion->prepare("DELETE FROM reactivos WHERE id_reactivo = ?");
    $stmt->execute([$_POST['id_reactivo']]);
    echo "ok";
    exit;
}

// GUARDAR
$id_reactivo = $_POST['id_reactivo'] ?? null;

if ($id_reactivo) {
    $stmt = $conexion->prepare("
        UPDATE reactivos
        SET orden_pipeteo=?, reactivo=?, volumen=?, unidad_medida=?, updated_by=?, updated_date=NOW()
        WHERE id_reactivo=?
    ");
    $stmt->execute([
        $_POST['orden_pipeteo'],
        $_POST['reactivo'],
        $_POST['volumen'],
        $_POST['unidad_medida'],
        $id_usuario,
        $id_reactivo
    ]);
} else {
    $stmt = $conexion->prepare("
        INSERT INTO reactivos
        (id_analisis, orden_pipeteo, reactivo, volumen, unidad_medida, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['id_analisis'],
        $_POST['orden_pipeteo'],
        $_POST['reactivo'],
        $_POST['volumen'],
        $_POST['unidad_medida'],
        $id_usuario
    ]);
}

echo "ok";
