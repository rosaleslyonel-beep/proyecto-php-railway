<?php
require_once "../config/database.php";
session_start();

// Recibir datos
$id_muestra = $_GET['id_muestra'] ?? null;
$id_protocolo = $_GET['id_protocolo'] ?? null;

if (!$id_muestra || !$id_protocolo) {
    echo "Datos incompletos para eliminar la muestra.";
    exit;
}

try {

    // 🔒 VALIDAR ESTADO DEL PROTOCOLO
    $stmt = $conexion->prepare("SELECT estado FROM protocolos WHERE id_protocolo = ?");
    $stmt->execute([$id_protocolo]);
    $protocolo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$protocolo) {
        die("El protocolo no existe.");
    }

    $estado = $protocolo['estado'] ?? 'BORRADOR';

    if ($estado !== 'BORRADOR') {
        header("Location: ../gestion_protocolos.php?id={$id_protocolo}&tab=tab_muestras&error=" . urlencode("El protocolo ya no permite eliminar muestras."));
        exit;
    }

    // ELIMINAR
    $stmt = $conexion->prepare("DELETE FROM muestras WHERE id_muestra = ?");
    $stmt->execute([$id_muestra]);

    header("Location: ../gestion_protocolos.php?id={$id_protocolo}&tab=tab_muestras&msg=eliminado");
    exit;

} catch (PDOException $e) {
    echo "Error al eliminar la muestra: " . $e->getMessage();
}