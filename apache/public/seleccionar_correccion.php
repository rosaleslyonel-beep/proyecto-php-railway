<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$id_protocolo = isset($_GET['id_protocolo']) ? (int)$_GET['id_protocolo'] : 0;
if ($id_protocolo <= 0) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $conexion->prepare("SELECT * FROM protocolos WHERE id_protocolo = ?");
$stmt->execute([$id_protocolo]);
$protocolo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$protocolo) {
    die("No se encontró el protocolo.");
}

$stmtEmisiones = $conexion->prepare("
    SELECT id_emision, fecha_emision, tipo_emision, resultados_incluidos_json
    FROM protocolo_emisiones_resultados
    WHERE id_protocolo = ?
    ORDER BY fecha_emision DESC, id_emision DESC
");
$stmtEmisiones->execute([$id_protocolo]);
$emisiones = $stmtEmisiones->fetchAll(PDO::FETCH_ASSOC);

$resultadoIdsEmitidos = [];
foreach ($emisiones as $emision) {
    $ids = json_decode($emision['resultados_incluidos_json'] ?? '[]', true);
    if (is_array($ids)) {
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $resultadoIdsEmitidos[$id] = true;
            }
        }
    }
}
$resultadoIdsEmitidos = array_keys($resultadoIdsEmitidos);

$filas = [];
if (!empty($resultadoIdsEmitidos)) {
    $placeholders = implode(',', array_fill(0, count($resultadoIdsEmitidos), '?'));
    $params = array_merge([$id_protocolo], $resultadoIdsEmitidos);

    $stmt = $conexion->prepare("
        SELECT
            m.id_muestra,
            m.tipo_muestra,
            a.id_analisis,
            a.nombre_estudio,
            ra.id_resultado,
            ra.created_date AS resultado_creado,
            COALESCE(ma.estado_resultado, 'ACTIVO') AS estado_resultado
        FROM muestras m
        JOIN muestra_analisis ma ON ma.id_muestra = m.id_muestra
        JOIN analisis_laboratorio a ON a.id_analisis = ma.id_analisis
        JOIN resultados_analisis ra ON ra.id_muestra = ma.id_muestra AND ra.id_analisis = ma.id_analisis
        WHERE m.id_protocolo = ?
          AND ra.id_resultado IN ($placeholders)
          AND COALESCE(ma.estado_resultado, 'ACTIVO') = 'ACTIVO'
        ORDER BY m.id_muestra, a.nombre_estudio
    ");
    $stmt->execute($params);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$agrupado = [];
foreach ($filas as $fila) {
    $agrupado[$fila['id_muestra']]['tipo_muestra'] = $fila['tipo_muestra'];
    $agrupado[$fila['id_muestra']]['analisis'][] = $fila;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar corrección</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background:#f7f7f7; color:#222; }
        .contenedor { max-width: 980px; margin:0 auto; background:#fff; border:1px solid #ddd; border-radius:10px; padding:24px; }
        .acciones { margin-bottom:16px; display:flex; gap:10px; flex-wrap:wrap; }
        .btn { display:inline-block; padding:10px 14px; border-radius:6px; text-decoration:none; color:#fff; background:#6c757d; font-weight:bold; border:none; cursor:pointer; }
        .btn.pri { background:#fd7e14; }
        .bloque { border:1px solid #ddd; border-radius:8px; margin-bottom:18px; overflow:hidden; }
        .bloque h3 { margin:0; padding:12px 14px; background:#f1f3f5; }
        .item { display:flex; gap:12px; padding:12px 14px; border-top:1px solid #eee; align-items:flex-start; }
        .help { background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding:12px; border-radius:6px; margin-bottom:16px; }
        .mini { color:#6c757d; font-size: 12px; }
        .emit-list { background:#f8f9fa; border:1px solid #dee2e6; padding:12px; border-radius:6px; margin-bottom:16px; }
    </style>
</head>
<body>
<div class="contenedor">
    <div class="acciones">
        <a class="btn" href="gestion_protocolos.php?id=<?= (int)$id_protocolo ?>&tab=tab_resultados">← Regresar</a>
    </div>

    <h2>Crear corrección</h2>
    <p><strong>Protocolo:</strong> <?= htmlspecialchars($protocolo['correlativo'] ?: ('ID ' . $protocolo['id_protocolo'])) ?></p>

    <?php if (empty($emisiones)): ?>
        <div class="help">
            No hay informes emitidos para este protocolo. La corrección se habilita únicamente después de generar resultados.
        </div>
    <?php else: ?>
        <div class="emit-list">
            <strong>Emisiones registradas:</strong>
            <ul style="margin:8px 0 0 18px;">
                <?php foreach ($emisiones as $emision): ?>
                    <li>
                        <?= htmlspecialchars($emision['tipo_emision'] ?: 'EMISION') ?>
                        · <?= !empty($emision['fecha_emision']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($emision['fecha_emision']))) : '—' ?>
                        · <a href="vista_previa_resultados.php?id_protocolo=<?= (int)$id_protocolo ?>&id_emision=<?= (int)$emision['id_emision'] ?>" target="_blank">Ver emisión</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="help">
        Solo se muestran análisis que ya fueron incluidos en al menos una emisión de resultados y que siguen activos en el protocolo actual.
    </div>

    <?php if (!$filas): ?>
        <p>No hay análisis emitidos disponibles para corrección.</p>
    <?php else: ?>
        <form method="post" action="crear_correccion.php">
            <input type="hidden" name="id_protocolo" value="<?= (int)$id_protocolo ?>">
            <?php foreach ($agrupado as $idMuestra => $bloque): ?>
                <div class="bloque">
                    <h3>Muestra <?= (int)$idMuestra ?> - <?= htmlspecialchars($bloque['tipo_muestra']) ?></h3>
                    <?php foreach ($bloque['analisis'] as $a): ?>
                        <label class="item">
                            <input type="checkbox" name="selecciones[]" value="<?= (int)$a['id_muestra'] ?>|<?= (int)$a['id_analisis'] ?>">
                            <div>
                                <strong><?= htmlspecialchars($a['nombre_estudio']) ?></strong><br>
                                <small>ID análisis: <?= (int)$a['id_analisis'] ?> · Resultado emitido: <?= (int)$a['id_resultado'] ?></small>
                                <?php if (!empty($a['resultado_creado'])): ?>
                                    <div class="mini">Resultado ingresado: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($a['resultado_creado']))) ?></div>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn pri" onclick="return confirm('¿Desea crear el protocolo de corrección con los análisis seleccionados?');">
                Crear protocolo de corrección
            </button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
