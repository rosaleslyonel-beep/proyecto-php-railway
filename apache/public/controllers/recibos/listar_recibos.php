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
        SELECT id_recibo,
               numero_recibo,
               fecha_recibo,
               monto,
               observaciones,
               anulado
          FROM public.protocolo_recibos
         WHERE id_protocolo = :id_protocolo
         ORDER BY fecha_recibo DESC, id_recibo DESC
    ");
    $stmt->execute([':id_protocolo' => $id_protocolo]);

    echo json_encode([
        'ok' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al listar recibos',
        'detalle' => $e->getMessage()
    ]);
}
?>