<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$busqueda = trim($_GET['busqueda'] ?? '');
$id_cliente = intval($_GET['id_cliente'] ?? 0);

$sql = "SELECT id_finca, nombre_finca FROM fincas 
        WHERE id_cliente = :id_cliente 
        AND (nombre_finca ILIKE :busqueda OR CAST(id_finca AS TEXT) ILIKE :busqueda)
        ORDER BY nombre_finca LIMIT 50";

$stmt = $conexion->prepare($sql);
$stmt->execute([
    ':id_cliente' => $id_cliente,
    ':busqueda' => "%$busqueda%"
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
