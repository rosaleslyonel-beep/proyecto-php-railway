<?php
session_start();
//require_once __DIR__ . '/../config/helpers.php'; // debe dejar $conexion como PDO
require_once "../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
     header("Location: ../index.php");
    exit();
}

$rol = strtolower(trim($_SESSION['rol'] ?? ''));
if (!in_array($rol, ['admin', 'recepcion'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$id_protocolo  = (int)($_POST['id_protocolo'] ?? 0);
$recibo_numero = trim($_POST['recibo_numero'] ?? '');
$recibo_fecha  = trim($_POST['recibo_fecha'] ?? '');
$recibo_monto  = trim($_POST['recibo_monto'] ?? '');
$id_usuario    = (int)($_SESSION['id_usuario'] ?? 0);

if ($id_protocolo <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Protocolo inválido']);
    exit;
}

if ($recibo_numero === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Debe ingresar número de recibo']);
    exit;
}

if ($recibo_fecha === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Debe ingresar fecha de recibo']);
    exit;
}

if ($recibo_monto === '' || !is_numeric($recibo_monto)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Debe ingresar un monto válido']);
    exit;
}

try {
    $stmt = $conexion->prepare("
        UPDATE public.protocolos
           SET pago_confirmado = true,
               recibo_numero = :recibo_numero,
               recibo_fecha = :recibo_fecha,
               recibo_monto = :recibo_monto,
               recibo_registrado_por = :id_usuario,
               recibo_registrado_fecha = NOW()
         WHERE id_protocolo = :id_protocolo
    ");

    $stmt->execute([
        ':recibo_numero' => $recibo_numero,
        ':recibo_fecha'  => $recibo_fecha,
        ':recibo_monto'  => $recibo_monto,
        ':id_usuario'    => $id_usuario ?: null,
        ':id_protocolo'  => $id_protocolo
    ]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Recibo registrado correctamente'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al registrar recibo',
        'detalle' => $e->getMessage()
    ]);
}
