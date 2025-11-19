<?php
session_start();
require_once "config/database.php";

// Registro de clientes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["registro"])) {
    $nombre = $_POST["nombre"];
    $correo = $_POST["correo"];
    $contrasena = password_hash($_POST["contrasena"], PASSWORD_BCRYPT);

    $stmt = $conexion->prepare("INSERT INTO clientes (nombre, correo, contrasena) VALUES (:nombre, :correo, :contrasena)");
    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":correo", $correo);
    $stmt->bindParam(":contrasena", $contrasena);
    $stmt->execute();
    echo "Registro exitoso. Ahora puedes iniciar sesión.";
}

// Inicio de sesión de clientes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $correo = $_POST["correo"];
    $contrasena = $_POST["contrasena"];

    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE correo = :correo");
    $stmt->bindParam(":correo", $correo);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente && password_verify($contrasena, $cliente["contrasena"])) {
        $_SESSION["cliente"] = $cliente["nombre"];
        $_SESSION["id_cliente"] = $cliente["id"];
        header("Location: solicitud_muestra.php");
        exit();
    } else {
        echo "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Portal de Clientes</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <h2>Registro de Cliente</h2>
    <form method="POST">
        <input type="hidden" name="registro" value="1">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>
        <label>Correo:</label>
        <input type="email" name="correo" required>
        <label>Contraseña:</label>
        <input type="password" name="contrasena" required>
        <button type="submit">Registrarse</button>
    </form>

    <h2>Inicio de Sesión</h2>
    <form method="POST">
        <input type="hidden" name="login" value="1">
        <label>Correo:</label>
        <input type="email" name="correo" required>
        <label>Contraseña:</label>
        <input type="password" name="contrasena" required>
        <button type="submit">Iniciar Sesión</button>
    </form>
</body>
</html>
