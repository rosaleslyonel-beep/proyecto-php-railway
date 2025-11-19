<?php
session_start();
require_once "../config/database.php";

// Crear un nuevo usuario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_user"])) {
    $nombre = $_POST["nombre"];
    $correo = $_POST["correo"];
    $contrasena = password_hash($_POST["contrasena"], PASSWORD_DEFAULT);
    $id_rol = $_POST["id_rol"];
    $estado = $_POST["estado"];

    try {
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, contrasena, id_rol, estado) 
                                    VALUES (:nombre, :correo, :contrasena, :id_rol, :estado)");
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":correo", $correo);
        $stmt->bindParam(":contrasena", $contrasena);
        $stmt->bindParam(":id_rol", $id_rol);
        $stmt->bindParam(":estado", $estado);
        $stmt->execute();
        header("Location: ../gestion_usuarios.php?success=creado");
    } catch (PDOException $e) {
        header("Location: ../gestion_usuarios.php?error=crear");
    }
    exit();
}

// Actualizar un usuario existente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_user"])) {
    $id_usuario = $_POST["id_usuario"];
    $nombre = $_POST["nombre"];
    $correo = $_POST["correo"];
    $id_rol = $_POST["id_rol"];
    $estado = $_POST["estado"];

    try {
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo, id_rol = :id_rol, estado = :estado 
                                    WHERE id_usuario = :id");
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":correo", $correo);
        $stmt->bindParam(":id_rol", $id_rol);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id", $id_usuario);
        $stmt->execute();
        header("Location: ../gestion_usuarios.php?success=actualizado");
    } catch (PDOException $e) {
        header("Location: ../gestion_usuarios.php?error=actualizar");
    }
    exit();
}
?>
