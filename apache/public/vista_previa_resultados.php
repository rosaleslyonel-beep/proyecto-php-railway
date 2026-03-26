<?php
session_start();
require_once "config/helpers.php";
require_once "config/database.php";
require_once "helpers_resultados_preview.php";

$id_emision = isset($_GET['id_emision']) ? (int)$_GET['id_emision'] : 0;
$id_protocolo = isset($_GET['id_protocolo']) ? (int)$_GET['id_protocolo'] : 0;

try {
    if ($id_emision > 0) {
        $stmt = $conexion->prepare("
            SELECT er.*, p.correlativo
            FROM protocolo_emisiones_resultados er
            JOIN protocolos p ON p.id_protocolo = er.id_protocolo
            WHERE er.id_emision = ?
        ");
        $stmt->execute([$id_emision]);
        $emision = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emision) {
            throw new RuntimeException('No se encontró la emisión solicitada.');
        }

        if (!empty($emision['snapshot_html'])) {
            echo $emision['snapshot_html'];
            exit;
        }

        $resultadoIds = [];
        $datosJson = json_decode($emision['resultados_incluidos_json'] ?? '[]', true);
        if (is_array($datosJson)) {
            $resultadoIds = array_values(array_filter(array_map('intval', $datosJson)));
        }

        echo rp_construir_html_preview($conexion, (int)$emision['id_protocolo'], [
            'resultado_ids' => $resultadoIds,
            'titulo' => 'Vista de emisión de resultados',
            'subtitulo_extra' => 'Emisión #' . $emision['id_emision'] . ' · ' . ($emision['tipo_emision'] ?? 'EMISION'),
            'mostrar_acciones' => true,
            'volver_url' => 'gestion_protocolos.php?id=' . (int)$emision['id_protocolo'] . '&tab=tab_resultados'
        ]);
        exit;
    }

    if ($id_protocolo <= 0) {
        throw new RuntimeException('Falta id_protocolo.');
    }

    $stmt = $conexion->prepare("
        SELECT ra.id_resultado
        FROM resultados_analisis ra
        JOIN muestras m ON m.id_muestra = ra.id_muestra
        WHERE m.id_protocolo = ?
        ORDER BY ra.id_resultado
    ");
    $stmt->execute([$id_protocolo]);
    $resultadoIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    echo rp_construir_html_preview($conexion, $id_protocolo, [
        'resultado_ids' => $resultadoIds,
        'titulo' => 'Vista previa de resultados',
        'mostrar_acciones' => true,
        'volver_url' => 'gestion_protocolos.php?id=' . $id_protocolo . '&tab=tab_resultados'
    ]);
} catch (Throwable $e) {
    echo 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}