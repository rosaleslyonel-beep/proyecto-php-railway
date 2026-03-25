<?php
require_once "../config/database.php";

// Recibir datos
$id_muestra = $_GET['id_muestra'] ?? null;
$id_protocolo = $_GET['id_protocolo'] ?? null;

if (!$id_muestra || !$id_protocolo) {
    echo "Datos incompletos para eliminar la muestra.";
    exit;
}

try {
    $stmt = $conexion->prepare("DELETE FROM muestras WHERE id_muestra = ?");
    $stmt->execute([$id_muestra]);

    // Regresar al protocolo
    header("Location: ../gestion_protocolos.php?id=".$id_protocolo."&tab=tab_muestras");
    exit;

} catch (PDOException $e) {
    echo "Error al eliminar la muestra: " . $e->getMessage();
}
