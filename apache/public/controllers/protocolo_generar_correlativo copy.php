<?php
session_start();
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$rol = strtolower(trim($_SESSION['usuario']['rol_nombre']  ?? ''));
if (!in_array($rol, ['admin', 'recepcion'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$id_protocolo = (int)($_POST['id_protocolo'] ?? 0);
$forzar       = (int)($_POST['forzar'] ?? 0);
$motivo       = trim($_POST['motivo'] ?? '');

if ($id_protocolo <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Protocolo inválido']);
    exit;
}

try {
    $conexion->beginTransaction();

    $stmt = $conexion->prepare("
        SELECT id_protocolo,
               id_tipo_protocolo,
               fecha,
               correlativo
          FROM public.protocolos
         WHERE id_protocolo = :id_protocolo
         FOR UPDATE
    ");
    $stmt->execute([':id_protocolo' => $id_protocolo]);
    $protocolo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$protocolo) {
        $conexion->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'mensaje' => 'Protocolo no encontrado']);
        exit;
    }

    if (!empty($protocolo['correlativo'])) {
        $conexion->commit();
        echo json_encode([
            'ok' => true,
            'correlativo' => $protocolo['correlativo'],
            'mensaje' => 'El protocolo ya tiene correlativo'
        ]);
        exit;
    }

    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS cantidad
          FROM public.protocolo_recibos
         WHERE id_protocolo = :id_protocolo
           AND anulado = false
    ");
    $stmt->execute([':id_protocolo' => $id_protocolo]);
    $cantidadRecibos = (int)$stmt->fetchColumn();

    if ($cantidadRecibos <= 0) {
        if ($forzar !== 1) {
            $conexion->rollBack();
            http_response_code(409);
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se puede generar correlativo sin recibos registrados'
            ]);
            exit;
        }

        if ($motivo === '') {
            $conexion->rollBack();
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Debe ingresar un motivo para forzar el correlativo sin recibos'
            ]);
            exit;
        }
    }

    $stmt = $conexion->prepare("
        SELECT public.generar_correlativo_protocolo(:id_tipo_protocolo, :fecha) AS correlativo
    ");
    $stmt->execute([
        ':id_tipo_protocolo' => $protocolo['id_tipo_protocolo'],
        ':fecha' => $protocolo['fecha'] ?: date('Y-m-d')
    ]);
    $correlativo = $stmt->fetchColumn();

   $stmt = $conexion->prepare("
    UPDATE public.protocolos
       SET correlativo = :correlativo,
           correlativo_forzado = :correlativo_forzado,
           correlativo_motivo = :correlativo_motivo,
           pago_confirmado = CASE WHEN :cantidad_recibos > 0 THEN true ELSE pago_confirmado END
     WHERE id_protocolo = :id_protocolo
");

$stmt->bindValue(':correlativo', $correlativo, PDO::PARAM_STR);
$stmt->bindValue(':correlativo_forzado', ($forzar === 1), PDO::PARAM_BOOL);
$stmt->bindValue(':correlativo_motivo', ($forzar === 1 ? $motivo : null), ($forzar === 1 ? PDO::PARAM_STR : PDO::PARAM_NULL));
$stmt->bindValue(':cantidad_recibos', $cantidadRecibos, PDO::PARAM_INT);
$stmt->bindValue(':id_protocolo', $id_protocolo, PDO::PARAM_INT);
$stmt->execute();

    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'correlativo' => $correlativo,
        'mensaje' => 'Correlativo generado correctamente'
    ]);
} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage() ,
        'detalle' => $e->getMessage()
    ]);
}