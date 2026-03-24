<?php
session_start();
require_once __DIR__ . '/../../config/helpers.php';

header('Content-Type: application/json');

$id_protocolo = (int)($_GET['id_protocolo'] ?? 0);

if ($id_protocolo <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Protocolo inválido']);
    exit;
}

try {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) FILTER (WHERE anulado = false) AS cantidad,
               COALESCE(SUM(monto) FILTER (WHERE anulado = false), 0) AS total
          FROM public.protocolo_recibos
         WHERE id_protocolo = :id_protocolo
    ");
    $stmt->execute([':id_protocolo' => $id_protocolo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $cantidad = (int)($row['cantidad'] ?? 0);
    $total    = (float)($row['total'] ?? 0);

    echo json_encode([
        'ok' => true,
        'cantidad' => $cantidad,
        'total' => $total,
        'tiene_recibos' => $cantidad > 0
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener resumen',
        'detalle' => $e->getMessage()
    ]);
}