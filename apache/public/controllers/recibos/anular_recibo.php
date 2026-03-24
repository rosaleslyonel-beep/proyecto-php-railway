<?php
session_start();
require_once __DIR__ . '/../../config/helpers.php';

header('Content-Type: application/json');

$rol = strtolower(trim($_SESSION['rol'] ?? ''));
if (!in_array($rol, ['admin', 'recepcion'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$id_recibo = (int)($_POST['id_recibo'] ?? 0);
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);

if ($id_recibo <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Recibo inválido']);
    exit;
}

try {
    $stmt = $conexion->prepare("
        UPDATE public.protocolo_recibos
           SET anulado = true,
               updated_by = :updated_by,
               updated_date = NOW()
         WHERE id_recibo = :id_recibo
           AND anulado = false
    ");

    $stmt->execute([
        ':updated_by' => $id_usuario ?: null,
        ':id_recibo'  => $id_recibo
    ]);

    if ($stmt->rowCount() <= 0) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró el recibo o ya estaba anulado'
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Recibo anulado correctamente'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al anular recibo',
        'detalle' => $e->getMessage()
    ]);
}