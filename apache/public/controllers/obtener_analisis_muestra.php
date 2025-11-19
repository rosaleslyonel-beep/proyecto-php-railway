<?php
require_once "../config/helpers.php";

$id_muestra = $_GET['id_muestra'] ?? null;
if (!$id_muestra) {
    echo json_encode([]);
    exit;
}

$stmt = $conexion->prepare("
    SELECT a.id_analisis, a.nombre_estudio, ma.precio_unitario AS precio
    FROM muestra_analisis ma
    JOIN analisis_laboratorio a ON ma.id_analisis = a.id_analisis
    WHERE ma.id_muestra = ?
");
$stmt->execute([$id_muestra]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
