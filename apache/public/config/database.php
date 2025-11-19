<?php
$host = "postgres_db";
$dbname = "mi_basedatos";
$user = "admin";
$password = "admin123";

$host = getenv('PGHOST') ?: 'postgres_db';
$port = getenv('PGPORT') ?: '5432';
$dbname   = getenv('PGDATABASE') ?: 'mi_basedatos';
$user = getenv('PGUSER') ?: 'admin';
$password = getenv('PGPASSWORD') ?: 'admin123';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conexion = new PDO($dsn, $user, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>