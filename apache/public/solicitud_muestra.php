<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["cliente"])) {
    header("Location: portal_cliente.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Solicitud de Muestras</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <?php include "views/menu.php"; ?>
    <h1>Solicitud de Examen de Muestras</h1>
    <form action="controllers/solicitud.php" method="POST">
        <label>Tipo de Muestra:</label>
        <input type="text" name="tipo_muestra" required>
        <label>Descripción:</label>
        <textarea name="descripcion" required></textarea>
        <button type="submit">Enviar Solicitud</button>
    </form>
</body>
</html>
