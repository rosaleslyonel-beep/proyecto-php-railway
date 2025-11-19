<?php
require_once "../config/database.php";
session_start();

if (!isset($_SESSION['usuario']) || !isset($_POST['id_tipo_protocolo'])) {
    header("Location: ../public/gestion_tipos_protocolo.php");
    exit();
}

$id = $_POST['id_tipo_protocolo'];
$nombre = $_POST['nombre_tipo'];
$prefijo = strtoupper(trim($_POST['prefijo']));
$descripcion = $_POST['descripcion'] ?? '';
$activo = $_POST['activo'] == '1' ? true : false;
$usuario = $_SESSION['usuario']['id_usuario'];

try {
    $stmt = $conexion->prepare("UPDATE tipos_protocolo 
        SET nombre_tipo = :nombre, 
            prefijo = :prefijo,
            descripcion = :descripcion,
            activo = :activo,
            updated_by = :user,
            updated_date = NOW()
        WHERE id_tipo_protocolo = :id");

    $stmt->execute([
        ':nombre' => $nombre,
        ':prefijo' => $prefijo,
        ':descripcion' => $descripcion,
        ':activo' => $activo,
        ':user' => $usuario,
        ':id' => $id
    ]);

    header("Location: ../gestion_tipos_protocolo.php?actualizado=1");
    exit();
} catch (PDOException $e) {
    header("Location: ../gestion_tipos_protocolo.php?error=1");
    exit();
}
