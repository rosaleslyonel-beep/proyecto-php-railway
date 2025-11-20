<?php
require_once "../config/database.php";
session_start();


$id_protocolo = (int)($_POST['id_protocolo'] ?? 0);

if ($id_protocolo <= 0) {
    http_response_code(400);
    exit('Protocolo inválido');
}

$pdo->beginTransaction();

// 1. Obtener datos básicos del protocolo
$sql = "SELECT id_protocolo, id_tipo_protocolo, fecha_creacion, correlativo
          FROM public.protocolos
         WHERE id_protocolo = :id
         FOR UPDATE";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id_protocolo]);
$protocolo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$protocolo) {
    $pdo->rollBack();
    http_response_code(404);
    exit('Protocolo no encontrado');
}

// Si ya tiene correlativo, solo lo devolvemos
if (!empty($protocolo['correlativo'])) {
    $pdo->commit();
    header('Content-Type: application/json');
    echo json_encode(['correlativo' => $protocolo['correlativo']]);
    exit;
}

// 2. Generar nuevo correlativo usando la función de Postgres
$sqlGen = "SELECT public.generar_correlativo_protocolo(:tipo, :fecha) AS correlativo";
$stmtGen = $pdo->prepare($sqlGen);
$stmtGen->execute([
    ':tipo'  => $protocolo['id_tipo_protocolo'],
    ':fecha' => $protocolo['fecha_creacion'] ?? date('Y-m-d'),
]);
$correlativo = $stmtGen->fetchColumn();

// 3. Guardar en el protocolo
$sqlUpd = "UPDATE public.protocolos
              SET correlativo = :corr
            WHERE id_protocolo = :id";
$stmtUpd = $pdo->prepare($sqlUpd);
$stmtUpd->execute([
    ':corr' => $correlativo,
    ':id'   => $id_protocolo,
]);

$pdo->commit();

header('Content-Type: application/json');
echo json_encode(['correlativo' => $correlativo]);
