<?php
require_once "config/helpers.php";
require_once "config/database.php";

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$id_muestra = $_GET['id_muestra'] ?? null;
$id_analisis = $_GET['id_analisis'] ?? null;
$id_protocolo = $_GET['id_protocolo'] ?? null;

if (!$id_muestra || !$id_analisis) {
    echo "Error: Faltan parámetros.";
    exit;
}

$stmt = $conexion->prepare("
    SELECT id_protocolo
    FROM muestras
    WHERE id_muestra = ?
");
$stmt->execute([$id_muestra]);
$id_protocolo = $id_protocolo ?: $stmt->fetchColumn();

if (!$id_protocolo) {
    echo "Error: No se pudo determinar el protocolo.";
    exit;
}

$stmt = $conexion->prepare("
    SELECT
        m.id_muestra,
        m.tipo_muestra,
        m.lote,
        ma.id_analisis,
        ra.id_resultado,
        ra.observaciones,
        ra.datos_json
    FROM muestras m
    JOIN muestra_analisis ma
        ON ma.id_muestra = m.id_muestra
    LEFT JOIN resultados_analisis ra
        ON ra.id_muestra = m.id_muestra
       AND ra.id_analisis = ma.id_analisis
    WHERE m.id_protocolo = ?
      AND ma.id_analisis = ?
    ORDER BY m.id_muestra
");
$stmt->execute([$id_protocolo, $id_analisis]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$filas) {
    echo "Error: No hay muestras asociadas a este análisis en el protocolo.";
    exit;
}

$resultado_emitido = false;
$stmt = $conexion->prepare("
    SELECT resultados_incluidos_json
    FROM protocolo_emisiones_resultados
    WHERE id_protocolo = ?
");
$stmt->execute([$id_protocolo]);
$emisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($filas as $fila) {
    $id_resultado_actual = $fila['id_resultado'] ?? null;
    if (!$id_resultado_actual) {
        continue;
    }
    foreach ($emisiones as $emision) {
        $ids = json_decode($emision['resultados_incluidos_json'] ?? '[]', true);
        if (is_array($ids) && in_array((int)$id_resultado_actual, array_map('intval', $ids), true)) {
            $resultado_emitido = true;
            break 2;
        }
    }
}

$soloLectura = $resultado_emitido;

$datos_base = [];
$observaciones_base = '';
foreach ($filas as $fila) {
    if (!empty($fila['datos_json'])) {
        $tmp = json_decode($fila['datos_json'], true);
        if (is_array($tmp)) {
            $datos_base = $tmp;
            $observaciones_base = $fila['observaciones'] ?? '';
            break;
        }
    }
}

$fecha = $datos_base['fecha'] ?? '';
$lote_antigeno = $datos_base['lote_antigeno'] ?? '';
$lote_antisuero = $datos_base['lote_antisuero'] ?? '';
$responsable = $datos_base['responsable'] ?? '';
$supervisor = $datos_base['supervisor'] ?? '';

$muestras_brucella = [];
foreach ($filas as $fila) {
    $datos = json_decode($fila['datos_json'] ?? '{}', true);
    if (!is_array($datos)) {
        $datos = [];
    }

    $identificacion = trim((string)($fila['lote'] ?? ''));
    if ($identificacion === '') {
        $identificacion = 'Muestra ' . $fila['id_muestra'];
    } else {
        $identificacion .= ' / Muestra ' . $fila['id_muestra'];
    }

    $muestras_brucella[] = [
        'id_muestra' => (int)$fila['id_muestra'],
        'identificacion' => $identificacion,
        'resultado' => $datos['resultado'] ?? '',
        'id_resultado' => $fila['id_resultado'] ?? null,
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resultado Brucella</title>
    <style>
        .wrap { font-family: Arial, sans-serif; max-width: 1100px; margin: 0 auto; background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 20px; }
        .btn-regresar { display:inline-block; text-decoration:none; padding:10px 14px; background:#6c757d; color:#fff; border-radius:6px; font-weight:bold; }
        .bloque-lectura { background:#e2e3e5; border:1px solid #c6c7c8; color:#41464b; padding:12px; margin:15px 0; border-radius:6px; }
        .titulo { text-align:center; margin:10px 0 20px; }
        .grid { display:grid; grid-template-columns:repeat(2, minmax(280px,1fr)); gap:14px 18px; margin:18px 0; }
        .campo { display:flex; flex-direction:column; gap:6px; }
        .campo label { font-weight:bold; color:#333; }
        .campo input, .campo textarea { width:100%; box-sizing:border-box; border:1px solid #ccc; border-radius:6px; padding:10px; font-size:15px; }
        .campo-full { grid-column:1 / -1; }
        .tabla-wrap { margin-top: 10px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #999; padding:8px; text-align:left; }
        thead th { background:#f3f3f3; }
        .radio-cell { text-align:center; }
        .acciones-finales { margin-top:18px; display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
        .btn-guardar { padding:10px 16px; border:none; background:#198754; color:#fff; font-weight:bold; border-radius:6px; cursor:pointer; }
        .help { background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding:10px 12px; border-radius:6px; margin:10px 0 16px; }
        @media (max-width: 800px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <a href="gestion_protocolos.php?id=<?= (int)$id_protocolo ?>&tab=tab_resultados" class="btn-regresar">← Regresar a Resultados</a>

    <h2 class="titulo">Resultado Brucella</h2>

    <div class="help">
        Este formulario agrupa todas las muestras del protocolo que llevan el análisis Brucella.
        Al guardar, cada resultado se registra individualmente por muestra.
    </div>

    <?php if ($soloLectura): ?>
        <div class="bloque-lectura">
            Este resultado ya fue emitido en un informe. Solo está disponible para consulta.
            Para modificarlo, debe crear una corrección.
        </div>
    <?php endif; ?>

    <form method="POST" action="guardar_resultado_brucella.php">
        <input type="hidden" name="id_protocolo" value="<?= (int)$id_protocolo ?>">
        <input type="hidden" name="id_analisis" value="<?= (int)$id_analisis ?>">

        <div class="grid">
            <div class="campo">
                <label>Fecha</label>
                <input type="date" name="fecha" value="<?= h($fecha) ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Lote del antígeno</label>
                <input type="text" name="lote_antigeno" value="<?= h($lote_antigeno) ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Lote del antisuero</label>
                <input type="text" name="lote_antisuero" value="<?= h($lote_antisuero) ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Responsable</label>
                <input type="text" name="responsable" value="<?= h($responsable) ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Supervisor</label>
                <input type="text" name="supervisor" value="<?= h($supervisor) ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo campo-full">
                <label>Observaciones</label>
                <textarea name="observaciones" rows="4" <?= $soloLectura ? 'readonly' : '' ?>><?= h($observaciones_base) ?></textarea>
            </div>
        </div>

        <div class="tabla-wrap">
            <table>
                <thead>
                    <tr>
                        <th colspan="3" style="text-align:center;">RESULTADOS</th>
                    </tr>
                    <tr>
                        <th>Identificación de muestra</th>
                        <th style="text-align:center;">Positivo</th>
                        <th style="text-align:center;">Negativo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($muestras_brucella as $i => $m): ?>
                        <tr>
                            <td>
                                <?= h($m['identificacion']) ?>
                                <input type="hidden" name="muestras[<?= $i ?>][id_muestra]" value="<?= (int)$m['id_muestra'] ?>">
                            </td>
                            <td class="radio-cell">
                                <input type="radio"
                                       name="muestras[<?= $i ?>][resultado]"
                                       value="positivo"
                                       <?= $m['resultado'] === 'positivo' ? 'checked' : '' ?>
                                       <?= $soloLectura ? 'disabled' : '' ?>>
                            </td>
                            <td class="radio-cell">
                                <input type="radio"
                                       name="muestras[<?= $i ?>][resultado]"
                                       value="negativo"
                                       <?= $m['resultado'] === 'negativo' ? 'checked' : '' ?>
                                       <?= $soloLectura ? 'disabled' : '' ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="acciones-finales">
            <?php if ($soloLectura): ?>
                <div class="bloque-lectura" style="margin:0;">
                    Este resultado ya fue emitido. Para modificarlo, debe crear una corrección.
                </div>
            <?php else: ?>
                <button type="submit" class="btn-guardar">Guardar</button>
            <?php endif; ?>
            <a href="gestion_protocolos.php?id=<?= (int)$id_protocolo ?>&tab=tab_resultados" class="btn-regresar">Regresar</a>
        </div>
    </form>
</div>
</body>
</html>
