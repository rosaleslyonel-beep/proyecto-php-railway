<?php
require_once "../config/helpers.php";
$q = $_GET['q'] ?? '';
$stmt = $conexion->prepare("SELECT id_analisis, nombre_estudio, precio FROM analisis_laboratorio 
                            WHERE LOWER(nombre_estudio) LIKE LOWER(?) ORDER BY nombre_estudio LIMIT 50");
$stmt->execute(["%$q%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
