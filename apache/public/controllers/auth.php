<?php
require_once "../config/database.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE correo = :correo");
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
        // Guardar la sesión correctamente
        $_SESSION['usuario'] = [
            'id_usuario'   => $usuario['id_usuario'],
            'nombre'       => $usuario['nombre'],
            'rol'          => $usuario['id_rol'],
            'rol_nombre'          => $usuario['rol'],
            'id_cliente'   => $usuario['id_cliente'] ?? null
        ];

        // Redireccionar al dashboard
        header("Location: ../dashboard.php");
        exit();
    } else {
        // Redireccionar al login con error
        header("Location: ../index.php?error=credenciales");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
