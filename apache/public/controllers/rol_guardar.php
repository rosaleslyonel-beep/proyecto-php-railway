<?php
require_once "../config/database.php";
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol_nombre'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$nombre = trim($_POST['nombre_rol']);
$id_usuario = $_SESSION['usuario']['id_usuario'];
$fecha = date('Y-m-d H:i:s');
$id = isset($_POST['id_rol']) && is_numeric($_POST['id_rol']) ? (int)$_POST['id_rol'] : null;

try {
    $stmt = $conexion->prepare("
        SELECT id_rol FROM roles 
        WHERE nombre_rol = :nombre" . ($id ? " AND id_rol <> :id" : "")
    );
    $stmt->bindValue(':nombre', $nombre);
    if ($id) $stmt->bindValue(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo "<h3>⚠️ Ya existe un rol con ese nombre.</h3>";
        echo "<a href='../gestion_roles.php?id=$id'>← Volver</a>";
        exit();
    }

    if ($id) {
        $stmt = $conexion->prepare("
            UPDATE roles SET 
                nombre_rol = :nombre,
                updated_by = :usuario,
                updated_date = :fecha
            WHERE id_rol = :id
        ");
        $stmt->execute([
            ':nombre' => $nombre,
            ':usuario' => $id_usuario,
            ':fecha' => $fecha,
            ':id' => $id
        ]);
    } else {
        $stmt = $conexion->prepare("
            INSERT INTO roles (nombre_rol, created_by, created_date)
            VALUES (:nombre, :usuario, :fecha)
        ");
        $stmt->execute([
            ':nombre' => $nombre,
            ':usuario' => $id_usuario,
            ':fecha' => $fecha
        ]);
    }

    header("Location: ../gestion_roles.php");
    exit();

} catch (PDOException $e) {
    echo "<h3>❌ Error al guardar rol:</h3><pre>" . $e->getMessage() . "</pre>";
    exit();
}
