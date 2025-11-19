<?php
/*$host = "postgres_db";
$dbname = "mi_basedatos";
$user = "admin";
$password = "admin123";*/

$host = getenv('PGHOST') ?: 'postgres_db';
$port = getenv('PGPORT') ?: '5432';
$dbname   = getenv('PGDATABASE') ?: 'mi_basedatos';
$user = getenv('PGUSER') ?: 'admin';
$password = getenv('PGPASSWORD') ?: 'admin123';

try {
    $conexion = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>