<?php
require_once "../config/database.php";

header('Content-Type: application/json');

if (!isset($_GET['id_cliente']) || !is_numeric($_GET['id_cliente'])) {
    echo json_encode([]);
    exit();
}

$id_cliente = (int)$_GET['id_cliente'];

try {
    $stmt = $conexion->prepare("SELECT id_finca, nombre_finca FROM fincas WHERE id_cliente = :id_cliente ORDER BY nombre_finca");
    $stmt->execute([':id_cliente' => $id_cliente]);

    $fincas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($fincas);
} catch (PDOException $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
