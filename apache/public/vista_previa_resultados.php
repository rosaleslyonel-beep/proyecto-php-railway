<?php
session_start();
require_once "config/helpers.php";
require_once "config/database.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$id_protocolo = isset($_GET['id_protocolo']) ? (int)$_GET['id_protocolo'] : 0;
if ($id_protocolo <= 0) {
    die("Protocolo no válido.");
}

$stmt = $conexion->prepare("
    SELECT
        p.*,
        c.nombre AS cliente_nombre,
        c.correo AS cliente_correo,
        c.telefono AS cliente_telefono,
        c.direccion AS cliente_direccion,
        f.nombre_finca,
        tp.nombre_tipo,
        tp.prefijo
    FROM protocolos p
    JOIN clientes c ON c.id_cliente = p.id_cliente
    LEFT JOIN fincas f ON f.id_finca = p.id_finca
    LEFT JOIN tipos_protocolo tp ON tp.id_tipo_protocolo = p.id_tipo_protocolo
    WHERE p.id_protocolo = ?
");
$stmt->execute([$id_protocolo]);
$protocolo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$protocolo) {
    die("No se encontró el protocolo.");
}

$stmtTotales = $conexion->prepare("
    SELECT
        COUNT(*) AS total_analisis,
        COUNT(ra.id_resultado) AS total_resultados
    FROM muestra_analisis ma
    JOIN muestras m ON m.id_muestra = ma.id_muestra
    LEFT JOIN resultados_analisis ra
        ON ra.id_muestra = ma.id_muestra
       AND ra.id_analisis = ma.id_analisis
    WHERE m.id_protocolo = ?
");
$stmtTotales->execute([$id_protocolo]);
$resumen = $stmtTotales->fetch(PDO::FETCH_ASSOC);
$totalAnalisis = (int)($resumen['total_analisis'] ?? 0);
$totalResultados = (int)($resumen['total_resultados'] ?? 0);
$resultadosCompletos = $totalAnalisis > 0 && $totalAnalisis === $totalResultados;

$stmtMuestras = $conexion->prepare("
    SELECT *
    FROM muestras
    WHERE id_protocolo = ?
    ORDER BY id_muestra ASC
");
$stmtMuestras->execute([$id_protocolo]);
$muestras = $stmtMuestras->fetchAll(PDO::FETCH_ASSOC);

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function renderResultadoAnalisis(array $fila): string {
    $tipo = strtolower((string)($fila['tipo_formulario'] ?? 'generico'));
    $html = '';

    if (!empty($fila['observaciones'])) {
        $html .= '<div><strong>Observaciones:</strong> ' . nl2br(h($fila['observaciones'])) . '</div>';
    }

    $datos = [];
    if (!empty($fila['datos_json'])) {
        $decoded = json_decode($fila['datos_json'], true);
        if (is_array($decoded)) {
            $datos = $decoded;
        }
    }

    if ($tipo === 'hi') {
        $campos = [
            'lote_antigeno' => 'Lote antígeno/antisuero',
            'fecha_elaboracion' => 'Fecha de elaboración',
            'prueba_para' => 'Prueba para',
            'hora_inicio' => 'Hora de inicio',
            'hora_fin' => 'Hora de finalización',
            'responsable' => 'Responsable',
            'lote_cp' => 'Lote control positivo',
            'resultado_cp' => 'Resultado CP',
            'lote_cn' => 'Lote control negativo',
            'resultado_cn' => 'Resultado CN',
        ];

        $html .= '<div class="resultado-grid">';
        foreach ($campos as $campo => $etiqueta) {
            if (!empty($fila[$campo])) {
                $html .= '<div><strong>' . h($etiqueta) . ':</strong> ' . h($fila[$campo]) . '</div>';
            }
        }
        $html .= '</div>';

        if (!empty($datos)) {
            $html .= '<div class="bloque-subresultado"><strong>Placas registradas:</strong>';
            foreach ($datos as $indice => $placa) {
                if (!is_array($placa)) {
                    continue;
                }
                $html .= '<div class="placa-resumen">';
                $html .= '<div class="placa-titulo">Placa ' . h((string)$indice) . '</div>';
                $primeros = array_slice($placa, 0, 12, true);
                foreach ($primeros as $celda => $valor) {
                    if ($valor === '' || $valor === null) {
                        continue;
                    }
                    $html .= '<span class="chip">' . h((string)$celda) . ': ' . h((string)$valor) . '</span>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
    } elseif ($tipo === 'idia') {
        $html .= '<div class="resultado-grid">';
        foreach (['lote_antigeno' => 'Lote antígeno', 'fecha_elaboracion' => 'Fecha de elaboración', 'prueba_para' => 'Prueba para'] as $campo => $etiqueta) {
            if (!empty($fila[$campo])) {
                $html .= '<div><strong>' . h($etiqueta) . ':</strong> ' . h($fila[$campo]) . '</div>';
            }
        }
        $html .= '</div>';

        if (!empty($datos['placas']) && is_array($datos['placas'])) {
            $html .= '<div class="bloque-subresultado"><strong>Lectura de placas:</strong> ';
            foreach ($datos['placas'] as $valor) {
                $texto = $valor === 'positivo' ? 'Positivo' : 'Negativo';
                $clase = $valor === 'positivo' ? 'chip chip-pos' : 'chip chip-neg';
                $html .= '<span class="' . $clase . '">' . h($texto) . '</span>';
            }
            $html .= '</div>';
        }
    } else {
        if (!empty($datos['archivo'])) {
            $html .= '<div><strong>Adjunto cargado:</strong> ' . h($datos['archivo']) . '</div>';
        }
    }

    if ($html === '') {
        $html = '<div class="texto-suave">Resultado registrado sin detalle adicional.</div>';
    }

    return $html;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vista previa de resultados - Protocolo <?= h($id_protocolo) ?></title>
    <style>
        body { font-family: Arial, sans-serif; color:#1f2937; margin:0; background:#f5f7fb; }
        .page { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
        .btn { display:inline-block; text-decoration:none; background:#0f766e; color:#fff; padding:10px 14px; border-radius:8px; font-weight:bold; }
        .btn.secondary { background:#475569; }
        .sheet { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,.05); overflow:hidden; }
        .header { padding:24px; border-bottom:1px solid #e5e7eb; }
        .title { font-size:26px; font-weight:700; color:#0f172a; margin:0 0 8px 0; }
        .subtitle { color:#475569; margin:0; }
        .status { display:inline-block; margin-top:12px; padding:6px 10px; border-radius:999px; background:#e0f2fe; color:#075985; font-weight:700; }
        .alert { margin:18px 24px 0; padding:12px 14px; border-radius:8px; }
        .alert.ok { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; }
        .alert.warn { background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; }
        .section { padding:24px; border-top:1px solid #e5e7eb; }
        .section h2 { margin:0 0 16px; font-size:20px; color:#111827; }
        .grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px 18px; }
        .field { background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; }
        .field .label { display:block; font-size:12px; text-transform:uppercase; color:#64748b; margin-bottom:4px; }
        .field .value { font-weight:600; color:#0f172a; }
        .muestra { border:1px solid #e5e7eb; border-radius:12px; margin-bottom:18px; overflow:hidden; }
        .muestra-head { background:#f8fafc; padding:14px 16px; border-bottom:1px solid #e5e7eb; }
        .muestra-title { margin:0; font-size:18px; }
        .muestra-meta { margin-top:6px; color:#64748b; font-size:14px; }
        .analisis { padding:16px; border-top:1px dashed #e5e7eb; }
        .analisis:first-child { border-top:none; }
        .analisis h4 { margin:0 0 8px; font-size:16px; color:#0f172a; }
        .estado-badge { display:inline-block; padding:5px 9px; border-radius:999px; background:#dcfce7; color:#166534; font-size:12px; font-weight:700; margin-bottom:10px; }
        .resultado-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:8px 14px; margin-bottom:10px; }
        .bloque-subresultado { margin-top:10px; }
        .placa-resumen { margin-top:8px; }
        .placa-titulo { font-weight:700; margin-bottom:6px; }
        .chip { display:inline-block; margin:4px 6px 0 0; background:#e2e8f0; color:#1e293b; padding:4px 8px; border-radius:999px; font-size:12px; }
        .chip-pos { background:#fee2e2; color:#991b1b; }
        .chip-neg { background:#dcfce7; color:#166534; }
        .texto-suave { color:#64748b; }
        @media print {
            .toolbar { display:none; }
            body { background:#fff; }
            .page { max-width:none; padding:0; }
            .sheet { box-shadow:none; border:none; }
        }
        @media (max-width: 768px) {
            .page { padding:14px; }
            .grid, .resultado-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="toolbar">
            <a class="btn secondary" href="gestion_protocolos.php?id=<?= h($id_protocolo) ?>&tab=tab_resultados">← Regresar a resultados</a>
            <div>
                <a class="btn secondary" href="javascript:window.print()">🖨️ Imprimir</a>
            </div>
        </div>

        <div class="sheet">
            <div class="header">
                <h1 class="title">Vista previa consolidada de resultados</h1>
                <p class="subtitle">Protocolo #<?= h($protocolo['id_protocolo']) ?> · Correlativo <?= h($protocolo['correlativo'] ?: 'Sin correlativo') ?></p>
                <span class="status">Estado del protocolo: <?= h($protocolo['estado'] ?? 'BORRADOR') ?></span>
            </div>

            <?php if ($resultadosCompletos): ?>
                <div class="alert ok">Todos los análisis del protocolo tienen resultado ingresado. La vista previa está lista para revisión.</div>
            <?php else: ?>
                <div class="alert warn">Aún faltan resultados por ingresar. Esta vista previa se muestra para revisión, pero todavía no está completa.</div>
            <?php endif; ?>

            <div class="section">
                <h2>Datos del protocolo</h2>
                <div class="grid">
                    <div class="field"><span class="label">Fecha</span><span class="value"><?= h($protocolo['fecha']) ?></span></div>
                    <div class="field"><span class="label">Tipo de protocolo</span><span class="value"><?= h($protocolo['nombre_tipo'] ?: 'N/D') ?></span></div>
                    <div class="field"><span class="label">Cliente</span><span class="value"><?= h($protocolo['cliente_nombre']) ?></span></div>
                    <div class="field"><span class="label">Finca</span><span class="value"><?= h($protocolo['nombre_finca'] ?: 'N/D') ?></span></div>
                    <div class="field"><span class="label">Correo</span><span class="value"><?= h($protocolo['correo'] ?: $protocolo['cliente_correo']) ?></span></div>
                    <div class="field"><span class="label">Teléfono</span><span class="value"><?= h($protocolo['telefono'] ?: $protocolo['cliente_telefono']) ?></span></div>
                    <div class="field"><span class="label">Dirección</span><span class="value"><?= h($protocolo['direccion'] ?: $protocolo['cliente_direccion']) ?></span></div>
                    <div class="field"><span class="label">Correlativo</span><span class="value"><?= h($protocolo['correlativo'] ?: 'Sin correlativo') ?></span></div>
                    <div class="field"><span class="label">Departamento</span><span class="value"><?= h($protocolo['departamento'] ?: 'N/D') ?></span></div>
                    <div class="field"><span class="label">Municipio</span><span class="value"><?= h($protocolo['municipio'] ?: 'N/D') ?></span></div>
                </div>
                <?php if (!empty($protocolo['observaciones'])): ?>
                    <div class="field" style="margin-top:12px;">
                        <span class="label">Observaciones generales</span>
                        <span class="value"><?= nl2br(h($protocolo['observaciones'])) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section">
                <h2>Muestras y resultados</h2>
                <?php if (!$muestras): ?>
                    <div class="texto-suave">No hay muestras registradas para este protocolo.</div>
                <?php else: ?>
                    <?php foreach ($muestras as $muestra): ?>
                        <div class="muestra">
                            <div class="muestra-head">
                                <h3 class="muestra-title">Muestra <?= h($muestra['tipo_muestra']) ?> (ID <?= h($muestra['id_muestra']) ?>)</h3>
                                <div class="muestra-meta">
                                    Lote: <?= h($muestra['lote'] ?: 'N/D') ?> · Cantidad: <?= h($muestra['cantidad'] ?: 'N/D') ?> · Edad: <?= h($muestra['edad'] ?: 'N/D') ?>
                                </div>
                            </div>

                            <?php
                            $stmtAnalisis = $conexion->prepare("
                                SELECT
                                    a.id_analisis,
                                    a.nombre_estudio,
                                    a.tipo_formulario,
                                    ra.*
                                FROM muestra_analisis ma
                                JOIN analisis_laboratorio a ON a.id_analisis = ma.id_analisis
                                LEFT JOIN resultados_analisis ra
                                    ON ra.id_muestra = ma.id_muestra
                                   AND ra.id_analisis = ma.id_analisis
                                WHERE ma.id_muestra = ?
                                ORDER BY a.nombre_estudio ASC
                            ");
                            $stmtAnalisis->execute([$muestra['id_muestra']]);
                            $analisis = $stmtAnalisis->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php if (!$analisis): ?>
                                <div class="analisis texto-suave">No hay análisis asignados a esta muestra.</div>
                            <?php else: ?>
                                <?php foreach ($analisis as $fila): ?>
                                    <div class="analisis">
                                        <div class="estado-badge"><?= !empty($fila['id_resultado']) ? 'Resultado ingresado' : 'Pendiente de resultado' ?></div>
                                        <h4><?= h($fila['nombre_estudio']) ?></h4>
                                        <?= renderResultadoAnalisis($fila) ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
