<?php
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$busqueda = trim($_GET['busqueda'] ?? '');
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;

$rol = $_SESSION['usuario']['rol_nombre'];
$id_cliente_sesion = $_SESSION['usuario']['id_cliente'] ?? null;

if ($busqueda === '') {
    $sql = "SELECT id_protocolo,id_tipo_protocolo, fecha , id_cliente
            FROM protocolos 
            where 1=1 ";

            
if ($rol === 'cliente') {
    $sql .= " and id_cliente = :id_cliente ";
}
 
           $sql .= " ORDER BY fecha DESC 
            LIMIT :limite OFFSET :offset";
    $stmt = $conexion->prepare($sql);
    if ($rol === 'cliente') {
        $stmt->bindValue(':id_cliente', $id_cliente_sesion, PDO::PARAM_INT);
    }
} else {
    $sql = "SELECT id_protocolo, id_tipo_protocolo, fecha , id_cliente
            FROM protocolos 
            WHERE (CAST(id_tipo_protocolo AS TEXT) ILIKE :busqueda 
               OR CAST(fecha AS TEXT) ILIKE :busqueda
               or CAST(id_protocolo AS TEXT) ILIKE :busqueda 
               or CAST(id_cliente AS TEXT) ILIKE :busqueda)";
            
if ($rol === 'cliente') {
    $sql .= " and id_cliente = :id_cliente ";
}else {


} 
            
           $sql .= "  ORDER BY fecha DESC 
            LIMIT :limite OFFSET :offset";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
    if ($rol === 'cliente') {
        $stmt->bindValue(':id_cliente', $id_cliente_sesion, PDO::PARAM_INT);
    }
    
}

$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
