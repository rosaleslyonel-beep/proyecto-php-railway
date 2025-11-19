<?php
require_once "../config/database.php";
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol_nombre'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$contrasena = $_POST['contrasena'] ?? '';
$rol = $_POST['id_rol'];
$estado = $_POST['estado'] ?? 1;
$id_cliente = $_POST['id_cliente'] ?? null;
$id_usuario_actual = $_SESSION['usuario']['id_usuario'];
$fecha_actual = date('Y-m-d H:i:s');
$is_update = isset($_POST['id_usuario']) && is_numeric($_POST['id_usuario']);

try {
    if ($is_update) {
        if (!empty($contrasena)) {
            $stmt = $conexion->prepare("
                UPDATE usuarios SET 
                    nombre = :nombre,
                    correo = :correo,
                    contrasena = crypt(:contrasena, gen_salt('bf')),
                    id_rol = :rol,
                    rol= (select nombre_rol from roles where id_rol = :rol) ,
                    estado = :estado,
                    id_cliente = :id_cliente,
                    updated_by = :actualizador,
                    updated_date = :fecha
                WHERE id_usuario = :id
            ");
            $stmt->execute([
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':contrasena' => $contrasena,
                ':rol' => $rol,
                ':estado' => $estado,
                ':id_cliente' => $id_cliente ?: null,
                ':actualizador' => $id_usuario_actual,
                ':fecha' => $fecha_actual,
                ':id' => $_POST['id_usuario']
            ]);
        } else {
            $stmt = $conexion->prepare("
                UPDATE usuarios SET 
                    nombre = :nombre,
                    correo = :correo,
                    id_rol = :rol,
                     rol= (select nombre_rol from roles where id_rol = :rol) ,
                    estado = :estado,
                    id_cliente = :id_cliente,
                    updated_by = :actualizador,
                    updated_date = :fecha
                WHERE id_usuario = :id
            ");
            $stmt->execute([
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':rol' => $rol,
                ':estado' => $estado,
                ':id_cliente' => $id_cliente ?: null,
                ':actualizador' => $id_usuario_actual,
                ':fecha' => $fecha_actual,
                ':id' => $_POST['id_usuario']
            ]);
        }
    } else {
        $stmt = $conexion->prepare("
            INSERT INTO usuarios (nombre, correo, contrasena, id_rol, rol,estado, id_cliente, created_by, created_date)
            VALUES (:nombre, :correo, crypt(:contrasena, gen_salt('bf')), :rol, (select nombre_rol from roles where id_rol = :rol),:estado, :id_cliente, :creador, :fecha)
        ");
        $stmt->execute([
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':contrasena' => $contrasena,
            ':rol' => $rol,
            ':estado' => $estado,
            ':id_cliente' => $id_cliente ?: null,
            ':creador' => $id_usuario_actual,
            ':fecha' => $fecha_actual
        ]);
    }

    header("Location: ../gestion_usuarios.php");
    exit();
} catch (PDOException $e) {
    echo "<h3>Error al guardar usuario:</h3><pre>" . $e->getMessage() . "</pre>";
    exit();
}
