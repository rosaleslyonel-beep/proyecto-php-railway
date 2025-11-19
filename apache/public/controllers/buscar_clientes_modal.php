<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$busqueda = trim($_GET['busqueda'] ?? '');
$sql = "SELECT id_cliente, nombre  FROM clientes 
        WHERE nombre ILIKE :busqueda OR CAST(id_cliente AS TEXT) ILIKE :busqueda 
        ORDER BY nombre LIMIT 50";
$stmt = $conexion->prepare($sql);
$stmt->execute([':busqueda' => "%$busqueda%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
