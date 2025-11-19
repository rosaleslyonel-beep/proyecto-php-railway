<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$id_cliente = $_GET['id_cliente'] ?? null;

if (!$id_cliente || !is_numeric($id_cliente)) {
    echo json_encode([]);
    exit();
}

$stmt = $conexion->prepare("SELECT id_finca, nombre_finca FROM fincas WHERE id_cliente = :id ORDER BY nombre_finca");
$stmt->execute([':id' => $id_cliente]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
