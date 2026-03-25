<?php
require_once "../config/database.php";
$id_cliente_sesion = $_SESSION['usuario']['id_cliente'] ?? null;
$rol = strtolower(trim($_SESSION['usuario']['rol_nombre']  ?? ''));

$busqueda = trim($_GET['busqueda'] ?? '');
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;


if ($busqueda === '') {
    if ($rol ==='cliente' ){
         
         $sql = "SELECT id_cliente, nombre 
            FROM clientes 
            WHERE  CAST(id_cliente AS TEXT) = :busqueda 
            ORDER BY id_cliente ASC 
            LIMIT :limite OFFSET :offset";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':busqueda', $id_cliente_sesion, PDO::PARAM_STR);
    }else{
        $sql = "SELECT id_cliente, nombre 
                FROM clientes 
                ORDER BY id_cliente ASC 
                LIMIT :limite OFFSET :offset";
        $stmt = $conexion->prepare($sql);
    }


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
