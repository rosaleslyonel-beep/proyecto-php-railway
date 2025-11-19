<?php
require_once "../config/database.php";

$busqueda = trim($_GET['busqueda'] ?? '');
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;

if ($busqueda === '') {
    $sql = "SELECT id_cliente, nombre 
            FROM clientes 
            ORDER BY id_cliente ASC 
            LIMIT :limite OFFSET :offset";
    $stmt = $conexion->prepare($sql);
} else {
    $sql = "SELECT id_cliente, nombre 
            FROM clientes 
            WHERE nombre ILIKE :busqueda OR CAST(id_cliente AS TEXT) ILIKE :busqueda 
            ORDER BY id_cliente ASC 
            LIMIT :limite OFFSET :offset";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
}

$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
