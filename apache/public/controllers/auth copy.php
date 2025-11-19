<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST["correo"];
    $contrasena = $_POST["contrasena"];

    $stmt = $conexion->prepare("SELECT u.id_usuario, u.nombre, u.contrasena, r.nombre_rol ,case when u.estado   then 'Activo' else 'Inactivo' end estado
                                FROM usuarios u 
                                JOIN roles r ON u.id_rol = r.id_rol 
                                WHERE correo = :correo ");
    $stmt->bindParam(":correo", $correo);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if($usuario && $usuario["estado"] == 'Inactivo') {
        header("Location: ../index.php?error=2");
        exit();
    } elseif ($usuario && password_verify($contrasena, $usuario["contrasena"])) {
        $_SESSION["usuario"] = $usuario["nombre"];
        $_SESSION["rol"] = $usuario["nombre_rol"];
        header("Location: ../dashboard.php");
        exit();
    } else {
        header("Location: ../index.php?error=1");
        exit();
    }
}
?>
