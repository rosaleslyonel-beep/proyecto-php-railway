<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION["id_cliente"])) {
        header("Location: ../portal_cliente.php");
        exit();
    }

    $id_cliente = $_SESSION["id_cliente"];
    $tipo_muestra = $_POST["tipo_muestra"];
    $descripcion = $_POST["descripcion"];

    $stmt = $conexion->prepare("INSERT INTO solicitudes (id_cliente, tipo_muestra, descripcion) VALUES (:id_cliente, :tipo_muestra, :descripcion)");
    $stmt->bindParam(":id_cliente", $id_cliente);
    $stmt->bindParam(":tipo_muestra", $tipo_muestra);
    $stmt->bindParam(":descripcion", $descripcion);
    $stmt->execute();

    echo "Solicitud enviada correctamente.";
}
?>
