<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["registrar_protocolo"])) {
    $tipo_protocolo = $_POST["id_tipo_protocolo"];
    $fecha = $_POST["fecha"];
    $cliente = $_POST["id_cliente"];
    $telefono = $_POST["telefono"];
    $correo = $_POST["correo"];
    $direccion = $_POST["direccion"];
    $firma_imagen = $_POST["firma_imagen"];

    try {
        $stmt = $conexion->prepare("INSERT INTO protocolos (id_tipo_protocolo, fecha, id_cliente, telefono, correo, direccion, firma_imagen) 
                                    VALUES (:tipo_protocolo, :fecha, :cliente, :telefono, :correo, :direccion, :firma_imagen)");
        $stmt->bindParam(":tipo_protocolo", $tipo_protocolo);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->bindParam(":cliente", $cliente);
        $stmt->bindParam(":telefono", $telefono);
        $stmt->bindParam(":correo", $correo);
        $stmt->bindParam(":direccion", $direccion);
        $stmt->bindParam(":firma_imagen", $firma_imagen);
        $stmt->execute();
        header("Location: ../protocolos.php?success=registrado");
    } catch (PDOException $e) {
        header("Location: ../protocolos.php?error=registro");
    }
    exit();
}
?>
