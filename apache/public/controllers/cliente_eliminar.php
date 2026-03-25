<?php
require_once "../config/database.php";
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$rol = strtolower(trim($_SESSION['usuario']['rol_nombre'] ?? ''));
$esCliente = ($rol === 'cliente');

if ($esCliente) {
    die("No tiene permiso para eliminar clientes.");
}

$id_cliente = $_GET['id'] ?? null;

if (!$id_cliente || !is_numeric($id_cliente)) {
    die("ID de cliente no válido.");
}

try {
    // Verificar que el cliente exista
    $stmt = $conexion->prepare("SELECT id_cliente, nombre FROM clientes WHERE id_cliente = :id");
    $stmt->execute([':id' => $id_cliente]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        die("El cliente no existe.");
    }

    /*
    |----------------------------------------------------------------------
    | Validaciones de relaciones
    | Ajusta los nombres de tabla/campo según tu base de datos real
    |----------------------------------------------------------------------
    */

    // Validar fincas / unidades productivas asociadas
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM fincas WHERE id_cliente = :id");
    $stmt->execute([':id' => $id_cliente]);
    $totalFincas = (int)$stmt->fetchColumn();

    if ($totalFincas > 0) {
        die("No se puede eliminar el cliente porque tiene unidades productivas o fincas asociadas.");
    }

    // Validar protocolos asociados
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM protocolos WHERE id_cliente = :id");
    $stmt->execute([':id' => $id_cliente]);
    $totalProtocolos = (int)$stmt->fetchColumn();

    if ($totalProtocolos > 0) {
        die("No se puede eliminar el cliente porque tiene protocolos asociados.");
    }

    // Eliminar cliente
    $stmt = $conexion->prepare("DELETE FROM clientes WHERE id_cliente = :id");
    $stmt->execute([':id' => $id_cliente]);

    header("Location: ../gestion_clientes.php?msg=cliente_eliminado");
    exit();

} catch (PDOException $e) {
    echo "<h3>⚠️ Error al eliminar cliente:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit();
}