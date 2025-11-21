<?php
require_once "../config/database.php";
session_start();
/*
// Asegurar que solo un administrador puede acceder
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol_nombre'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
*/
$nombre = $_POST['nombre'];
$nit = $_POST['nit'] ?? null;
$telefono = $_POST['telefono'] ?? null;
$correo = $_POST['correo'] ?? null;
$direccion = $_POST['direccion'] ?? null;
$id_usuario = $_SESSION['usuario']['id_usuario'];
$fecha_actual = date('Y-m-d H:i:s');

// Detectar si es nuevo o actualización
$is_update = isset($_POST['id_cliente']) && is_numeric($_POST['id_cliente']);

try {
    if ($is_update) {
        // Actualizar cliente existente
        $stmt = $conexion->prepare("
            UPDATE clientes SET 
                nombre = :nombre,                
                telefono = :telefono,
                correo = :correo,
                direccion = :direccion,
                updated_by = :usuario,
                updated_date = :fecha
            WHERE id_cliente = :id
        ");

        $stmt->execute([
            ':nombre' => $nombre, 
            ':telefono' => $telefono,
            ':correo' => $correo,
            ':direccion' => $direccion,
            ':usuario' => $id_usuario,
            ':fecha' => $fecha_actual,
            ':id' => $_POST['id_cliente']
        ]);

    } else {
        // Insertar nuevo cliente
        $stmt = $conexion->prepare("
            INSERT INTO clientes (nombre,   telefono, correo, direccion, created_by, created_date)
            VALUES (:nombre,  :telefono, :correo, :direccion, :usuario, :fecha)
        ");

        $stmt->execute([
            ':nombre' => $nombre, 
            ':telefono' => $telefono,
            ':correo' => $correo,
            ':direccion' => $direccion,
            ':usuario' => $id_usuario,
            ':fecha' => $fecha_actual
        ]);
    }

    // Redirigir nuevamente a gestión
    header("Location: ../gestion_clientes.php");
    exit();

} catch (PDOException $e) {
    echo "<h3>⚠️ Error al guardar cliente:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    exit();
}
