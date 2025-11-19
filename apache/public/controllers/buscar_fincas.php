<?php
require_once "../config/database.php";

header('Content-Type: application/json');

$busqueda = trim($_GET['busqueda'] ?? '');
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;
$id_cliente = $_GET['id_cliente'] ?? null;

if (!$id_cliente || !is_numeric($id_cliente)) {
    echo json_encode([]);
    exit();
}

if ($busqueda === '') {
    // Obtener sin filtro
    $sql = "SELECT id_finca, nombre_finca 
            FROM fincas 
            WHERE id_cliente = :id_cliente 
            ORDER BY nombre_finca 
            LIMIT :limite OFFSET :offset";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':id_cliente', $id_cliente, PDO::PARAM_INT);
} else {
    // Obtener con filtro por nombre o ID
    $sql = "SELECT id_finca, nombre_finca 
            FROM fincas 
            WHERE id_cliente = :id_cliente 
              AND (nombre_finca ILIKE :busqueda OR CAST(id_finca AS TEXT) ILIKE :busqueda)
            ORDER BY nombre_finca 
            LIMIT :limite OFFSET :offset";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
}

$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
