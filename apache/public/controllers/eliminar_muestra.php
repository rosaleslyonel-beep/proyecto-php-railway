<?php
require_once "../config/database.php";
session_start();

$id_muestra   = $_GET['id_muestra'] ?? null;
$id_protocolo = $_GET['id_protocolo'] ?? null;

if (!$id_muestra || !is_numeric($id_muestra)) {
    echo "Error: ID de muestra inválido.";
    exit();
}

try {

    // 🔍 VALIDACIÓN PREVIA (MEJOR UX)
    $stmtCheck = $conexion->prepare("
        SELECT COUNT(*) 
        FROM muestra_analisis
        WHERE id_muestra = :id_muestra
    ");
    $stmtCheck->execute([':id_muestra' => $id_muestra]);
    $tieneAnalisis = (int)$stmtCheck->fetchColumn();

    if ($tieneAnalisis > 0) {
        echo "No se puede eliminar la muestra porque tiene análisis asociados.";
        exit();
    }

    // 🧨 DELETE
    $stmt = $conexion->prepare("DELETE FROM muestras WHERE id_muestra = :id_muestra");
    $stmt->execute([':id_muestra' => $id_muestra]);

    header("Location: ../gestion_protocolos.php?id=$id_protocolo&tab=tab_muestras");
    exit();

} catch (Throwable $e) {

    echo "Error al eliminar la muestra: " . $e->getMessage();
}