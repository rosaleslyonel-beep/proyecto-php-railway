<?php
require_once "../config/database.php";
session_start();

$id_cliente_sesion = $_SESSION['usuario']['id_cliente'] ?? null;
$rol = strtolower(trim($_SESSION['usuario']['rol_nombre'] ?? ''));

$busqueda = trim($_GET['busqueda'] ?? '');
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;

if ($rol === 'cliente') {
    $sql = "SELECT id_cliente, nombre
            FROM clientes
            WHERE id_cliente = :id_cliente";

    if ($busqueda !== '') {
        $sql .= " AND (
                    nombre ILIKE :busqueda
                    OR CAST(id_cliente AS TEXT) ILIKE :busqueda
                 )";
    }

    $sql .= " ORDER BY id_cliente ASC
              LIMIT :limite OFFSET :offset";

    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':id_cliente', $id_cliente_sesion, PDO::PARAM_INT);

    if ($busqueda !== '') {
        $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
    }

} else {
    if ($busqueda === '') {
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                ORDER BY id_cliente ASC
                LIMIT :limite OFFSET :offset";
        $stmt = $conexion->prepare($sql);
    } else {
        $sql = "SELECT id_cliente, nombre
                FROM clientes
                WHERE nombre ILIKE :busqueda
                   OR CAST(id_cliente AS TEXT) ILIKE :busqueda
                ORDER BY id_cliente ASC
                LIMIT :limite OFFSET :offset";
        $stmt = $conexion->prepare($sql);
        $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
    }
}

$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));