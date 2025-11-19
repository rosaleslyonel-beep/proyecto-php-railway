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
    $sql = "SELECT id_analisis,nombre_estudio   
            FROM analisis_laboratorio 
            where 1=1 ";

         
           $sql .= " ORDER BY 1 DESC 
            LIMIT :limite OFFSET :offset";
    $stmt = $conexion->prepare($sql);
    
} else {
    $sql = "SELECT id_analisis, nombre_estudio 
            FROM analisis_laboratorio 
            WHERE (CAST(id_analisis AS TEXT) ILIKE :busqueda 
               OR CAST(nombre_estudio AS TEXT) ILIKE :busqueda
                )";
            
 
            
           $sql .= "  ORDER BY 1 DESC 
            LIMIT :limite OFFSET :offset";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
     
    
}

$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
