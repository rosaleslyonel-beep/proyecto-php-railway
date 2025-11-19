<?php
require_once "config/session.php";
require_once "config/helpers.php";
?>
<?php
 
require_once "config/database.php";

if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $correo = $_POST["correo"];
    $contrasena = password_hash($_POST["contrasena"], PASSWORD_BCRYPT);
    $rol = $_POST["rol"];

    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (:nombre, :correo, :contrasena, :rol)");
    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":correo", $correo);
    $stmt->bindParam(":contrasena", $contrasena);
    $stmt->bindParam(":rol", $rol);
    $stmt->execute();
    echo "Usuario agregado correctamente.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Configuración de Usuarios</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <?php include "views/menu.php"; ?>
    <h2>Agregar Usuario</h2>
    <form method="POST">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>
        <label>Correo:</label>
        <input type="email" name="correo" required>
        <label>Contraseña:</label>
        <input type="password" name="contrasena" required>
        <label>Rol:</label>
        <select name="rol">
            <option value="admin">Administrador</option>
            <option value="usuario">Usuario</option>
        </select>
        <button type="submit">Agregar Usuario</button>
    </form>
</body>
</html>
