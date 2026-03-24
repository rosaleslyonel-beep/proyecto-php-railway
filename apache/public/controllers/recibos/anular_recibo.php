<?php
session_start();
require_once __DIR__ . '/../../config/helpers.php';

header('Content-Type: application/json');

$rol = strtolower(trim($_SESSION['usuario']['rol_nombre']  ?? ''));
if (!in_array($rol, ['admin', 'recepcion'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$id_usuario    = (int)($_SESSION['id_usuario'] ?? 0);
$id_recibo     = (int)($_POST['id_recibo'] ?? 0);
$id_protocolo  = (int)($_POST['id'] ?? 0);
$numero_recibo = trim($_POST['numero_recibo'] ?? '');
$fecha_recibo  = trim($_POST['fecha_recibo'] ?? '');
$monto         = trim($_POST['monto'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

if ($id_protocolo <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Protocolo inválido']);
    exit;
}
if ($numero_recibo === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Ingrese número de recibo']);
    exit;
}
if ($fecha_recibo === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Ingrese fecha']);
    exit;
}
if ($monto === '' || !is_numeric($monto)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Ingrese monto válido']);
    exit;
}

try {
    if ($id_recibo > 0) {
        $stmt = $conexion->prepare("
            UPDATE public.protocolo_recibos
               SET numero_recibo = :numero_recibo,
                   fecha_recibo = :fecha_recibo,
                   monto = :monto,
                   observaciones = :observaciones,
                   updated_by = :updated_by,
                   updated_date = NOW()
             WHERE id_recibo = :id_recibo
        ");
        $stmt->execute([
            ':numero_recibo' => $numero_recibo,
            ':fecha_recibo'  => $fecha_recibo,
            ':monto'         => $monto,
            ':observaciones' => $observaciones !== '' ? $observaciones : null,
            ':updated_by'    => $id_usuario ?: null,
            ':id_recibo'     => $id_recibo
        ]);
    } else {
        $stmt = $conexion->prepare("
            INSERT INTO public.protocolo_recibos
                (id_protocolo, numero_recibo, fecha_recibo, monto, observaciones, created_by)
            VALUES
                (:id_protocolo, :numero_recibo, :fecha_recibo, :monto, :observaciones, :created_by)
        ");
        $stmt->execute([
            ':id_protocolo'  => $id_protocolo,
            ':numero_recibo' => $numero_recibo,
            ':fecha_recibo'  => $fecha_recibo,
            ':monto'         => $monto,
            ':observaciones' => $observaciones !== '' ? $observaciones : null,
            ':created_by'    => $id_usuario ?: null
        ]);
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Recibo guardado correctamente'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al guardar recibo',
        'detalle' => $e->getMessage()
    ]);
}