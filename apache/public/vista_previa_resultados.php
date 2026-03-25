<?php
session_start();
require_once "config/helpers.php";
require_once "config/database.php";

$id_protocolo = isset($_GET['id_protocolo']) ? (int)$_GET['id_protocolo'] : 0;

if ($id_protocolo <= 0) {
    echo "Error: Falta id_protocolo.";
    exit;
}

$stmt = $conexion->prepare("
    SELECT 
        p.*,
        c.nombre AS cliente_nombre,
        c.correo AS cliente_correo,
        c.telefono AS cliente_telefono,
        c.direccion AS cliente_direccion,
        f.nombre_finca,
        f.ubicacion AS finca_ubicacion,
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
    echo "No se encontró el protocolo.";
    exit;
}

$stmt = $conexion->prepare("
    SELECT 
        m.*,
        ma.id_analisis,
        ma.precio_unitario,
        ma.cantidad AS cantidad_analisis,
        a.nombre_estudio,
        a.tipo_formulario,
        ra.id_resultado,
        ra.observaciones,
        ra.datos_json,
        ra.lote_antigeno,
        ra.fecha_elaboracion,
        ra.prueba_para,
        ra.hora_inicio,
        ra.hora_fin,
        ra.responsable,
        ra.lote_cp,
        ra.resultado_cp,
        ra.lote_cn,
        ra.resultado_cn,
        ra.created_date AS resultado_created_date,
        ra.updated_date AS resultado_updated_date
    FROM muestras m
    JOIN muestra_analisis ma ON ma.id_muestra = m.id_muestra
    JOIN analisis_laboratorio a ON a.id_analisis = ma.id_analisis
    LEFT JOIN resultados_analisis ra 
        ON ra.id_muestra = m.id_muestra 
       AND ra.id_analisis = ma.id_analisis
    WHERE m.id_protocolo = ?
    ORDER BY m.id_muestra, a.nombre_estudio
");
$stmt->execute([$id_protocolo]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$muestras = [];
foreach ($filas as $fila) {
    $id_muestra = (int)$fila['id_muestra'];
    if (!isset($muestras[$id_muestra])) {
        $muestras[$id_muestra] = [
            'muestra' => [
                'id_muestra' => $fila['id_muestra'],
                'tipo_muestra' => $fila['tipo_muestra'],
                'lote' => $fila['lote'],
                'cantidad' => $fila['cantidad'],
                'edad' => $fila['edad'],
                'variedad' => $fila['variedad'],
                'descripcion' => $fila['descripcion'],
                'fecha_recepcion' => $fila['fecha_recepcion'],
                'estado_muestra' => $fila['estado_muestra'],
                'tipo_vacuna' => $fila['tipo_vacuna'],
                'marca_vacuna' => $fila['marca_vacuna'],
                'dosis' => $fila['dosis'],
                'fecha_elaboracion' => $fila['fecha_elaboracion'],
                'fecha_vencimiento' => $fila['fecha_vencimiento'],
                'prueba_solicitada' => $fila['prueba_solicitada'],
            ],
            'analisis' => []
        ];
    }

    $muestras[$id_muestra]['analisis'][] = $fila;
}

function h($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function fmtFecha($valor) {
    if (!$valor) return '—';
    $ts = strtotime((string)$valor);
    return $ts ? date('d/m/Y', $ts) : h($valor);
}

function fmtFechaHora($valor) {
    if (!$valor) return '—';
    $ts = strtotime((string)$valor);
    return $ts ? date('d/m/Y H:i', $ts) : h($valor);
}

function adjuntoUrl($archivo) {
    $archivo = basename((string)$archivo);
    return "ver_resultado_adjunto.php?file=" . rawurlencode($archivo);
}

function renderAdjuntoInline($archivo) {
    if (!$archivo) {
        return '';
    }

    $archivo = basename((string)$archivo);
    $url = adjuntoUrl($archivo);
    $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return '
            <div class="adjunto-box">
                <div class="adjunto-label">Adjunto:</div>
                <img src="' . h($url) . '" alt="Adjunto" class="adjunto-img">
                <div class="adjunto-link">
                    <a href="' . h($url) . '" target="_blank">Abrir archivo</a>
                </div>
            </div>
        ';
    }

    if ($ext === 'pdf') {
        return '
            <div class="adjunto-box">
                <div class="adjunto-label">Adjunto:</div>
                <iframe src="' . h($url) . '" class="adjunto-pdf"></iframe>
                <div class="adjunto-link">
                    <a href="' . h($url) . '" target="_blank">Abrir PDF en otra pestaña</a>
                </div>
            </div>
        ';
    }

    return '
        <div class="adjunto-box">
            <div class="adjunto-label">Adjunto:</div>
            <div class="adjunto-link">
                <a href="' . h($url) . '" target="_blank">' . h($archivo) . '</a>
            </div>
        </div>
    ';
}

function renderResultado($fila) {
    $tipo = strtolower((string)($fila['tipo_formulario'] ?? 'generico'));
    $datos = json_decode($fila['datos_json'] ?? '{}', true);
    if (!is_array($datos)) {
        $datos = [];
    }

    if (!$fila['id_resultado']) {
        return '<div class="resultado-vacio">Resultado no ingresado.</div>';
    }

    if ($tipo === 'hi') {
        $html = '<div class="resultado-bloque">';
        $html .= '<div class="resultado-grid">';
        $html .= '<div><strong>Lote antígeno/antisuero:</strong> ' . h($fila['lote_antigeno']) . '</div>';
        $html .= '<div><strong>Fecha elaboración:</strong> ' . fmtFecha($fila['fecha_elaboracion']) . '</div>';
        $html .= '<div><strong>Prueba para:</strong> ' . h($fila['prueba_para']) . '</div>';
        $html .= '<div><strong>Hora inicio:</strong> ' . h($fila['hora_inicio']) . '</div>';
        $html .= '<div><strong>Hora fin:</strong> ' . h($fila['hora_fin']) . '</div>';
        $html .= '<div><strong>Responsable:</strong> ' . h($fila['responsable']) . '</div>';
        $html .= '<div><strong>Lote CP:</strong> ' . h($fila['lote_cp']) . '</div>';
        $html .= '<div><strong>Resultado CP:</strong> ' . h($fila['resultado_cp']) . '</div>';
        $html .= '<div><strong>Lote CN:</strong> ' . h($fila['lote_cn']) . '</div>';
        $html .= '<div><strong>Resultado CN:</strong> ' . h($fila['resultado_cn']) . '</div>';
        $html .= '</div>';

        $placas = is_array($datos) ? $datos : [];
        if (!empty($placas)) {
            $html .= '<div class="subtitulo-resultado">Placas registradas</div>';
            $html .= '<div class="placas-resumen">';
            foreach ($placas as $indice => $placa) {
                $totalCeldas = is_array($placa) ? count($placa) : 0;
                $html .= '<div class="placa-card"><strong>Placa ' . h($indice) . '</strong><br>Total celdas capturadas: ' . h($totalCeldas) . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="obs"><strong>Observaciones:</strong><br>' . nl2br(h($fila['observaciones'])) . '</div>';
        $html .= '</div>';
        return $html;
    }

    if ($tipo === 'idia') {
        $placas = $datos['placas'] ?? [];
        $total = is_array($placas) ? count($placas) : 0;
        $positivos = 0;
        if (is_array($placas)) {
            foreach ($placas as $p) {
                if ($p === 'positivo') $positivos++;
            }
        }
        $negativos = $total - $positivos;

        $html = '<div class="resultado-bloque">';
        $html .= '<div class="resultado-grid">';
        $html .= '<div><strong>Total placas:</strong> ' . h($total) . '</div>';
        $html .= '<div><strong>Positivas:</strong> ' . h($positivos) . '</div>';
        $html .= '<div><strong>Negativas:</strong> ' . h($negativos) . '</div>';
        $html .= '<div><strong>Fecha elaboración:</strong> ' . h($datos['fecha_elaboracion'] ?? '') . '</div>';
        $html .= '<div><strong>Fecha lectura:</strong> ' . h($datos['fecha_lectura'] ?? '') . '</div>';
        $html .= '<div><strong>Prueba para:</strong> ' . h($datos['prueba_para'] ?? '') . '</div>';
        $html .= '<div><strong>Procesada por:</strong> ' . h($datos['procesada_por'] ?? '') . '</div>';
        $html .= '<div><strong>Realizada por:</strong> ' . h($datos['realizada_por'] ?? '') . '</div>';
        $html .= '</div>';
        $html .= '<div class="obs"><strong>Observaciones:</strong><br>' . nl2br(h($fila['observaciones'])) . '</div>';
        $html .= '</div>';
        return $html;
    }

    $archivo = $datos['archivo'] ?? null;
    $html = '<div class="resultado-bloque">';
    $html .= '<div class="obs"><strong>Observaciones:</strong><br>' . nl2br(h($fila['observaciones'])) . '</div>';
    if ($archivo) {
        $html .= renderAdjuntoInline($archivo);
    }
    $html .= '</div>';
    return $html;
}

$totalAnalisis = count($filas);
$totalResultados = 0;
foreach ($filas as $fila) {
    if (!empty($fila['id_resultado'])) {
        $totalResultados++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vista previa de resultados</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; background: #f7f7f7; }
        .acciones { margin-bottom: 18px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 14px; background: #0b6b5d; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn.sec { background: #5f6b76; }
        .documento { max-width: 1100px; margin: 0 auto; background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
        .encabezado { border-bottom: 2px solid #0b6b5d; padding-bottom: 14px; margin-bottom: 18px; }
        .titulo { margin: 0 0 6px 0; font-size: 28px; color: #0b6b5d; }
        .subtitulo { color: #666; margin: 0; }
        .resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 22px; }
        .card { background: #f8fbfa; border: 1px solid #dceae6; border-radius: 8px; padding: 12px 14px; }
        .card .label { display: block; font-size: 12px; color: #65737e; margin-bottom: 4px; text-transform: uppercase; }
        .card .valor { font-size: 15px; font-weight: bold; }
        .seccion-titulo { font-size: 20px; color: #24323d; margin: 22px 0 12px; border-left: 5px solid #0b6b5d; padding-left: 10px; }
        .obs-general { background: #fff8e6; border: 1px solid #efd38d; border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; }
        .muestra-box { border: 1px solid #d8dee3; border-radius: 10px; overflow: hidden; margin-bottom: 22px; }
        .muestra-head { background: #eef5f3; padding: 14px 16px; border-bottom: 1px solid #d8dee3; }
        .muestra-head h3 { margin: 0 0 6px 0; color: #1f2d38; }
        .muestra-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; padding: 14px 16px; background: #fafafa; border-bottom: 1px solid #ececec; }
        .analisis-item { padding: 16px; border-top: 1px solid #efefef; }
        .analisis-item:first-child { border-top: none; }
        .analisis-head { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 10px; }
        .badge { display: inline-block; background: #dff4eb; color: #0b6b5d; border: 1px solid #a8d7c6; padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: bold; }
        .badge.pendiente { background: #fff0d7; color: #9b5c00; border-color: #e7c177; }
        .resultado-bloque { background: #fcfcfc; border: 1px solid #ececec; border-radius: 8px; padding: 14px; }
        .resultado-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 8px 14px; margin-bottom: 12px; }
        .subtitulo-resultado { margin: 10px 0 8px; font-weight: bold; color: #334; }
        .placas-resumen { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        .placa-card { background: #fff; border: 1px solid #e1e6ea; border-radius: 8px; padding: 10px 12px; min-width: 150px; }
        .obs { margin-top: 8px; }
        .resultado-vacio { background: #fff6ea; border: 1px solid #f0d29a; color: #875500; border-radius: 8px; padding: 10px 12px; }
        .adjunto-box { margin-top: 12px; }
        .adjunto-label { font-weight: bold; margin-bottom: 6px; }
        .adjunto-img { width: 100%; max-width: 760px; max-height: 540px; object-fit: contain; border: 1px solid #d6d6d6; border-radius: 8px; background: #fff; }
        .adjunto-pdf { width: 100%; height: 560px; border: 1px solid #d6d6d6; border-radius: 8px; background: #fff; }
        .adjunto-link { margin-top: 8px; }
        @media print {
            body { background: #fff; margin: 0; }
            .acciones { display: none; }
            .documento { box-shadow: none; border: none; max-width: 100%; margin: 0; padding: 0; }
            .muestra-box, .resultado-bloque, .card { break-inside: avoid; }
        }
        @media (max-width: 768px) {
            body { margin: 12px; }
            .documento { padding: 16px; }
            .titulo { font-size: 23px; }
            .adjunto-pdf { height: 380px; }
        }
    </style>
</head>
<body>
    <div class="acciones">
        <a href="gestion_protocolos.php?id=<?= (int)$id_protocolo ?>&tab=tab_resultados" class="btn sec">← Regresar</a>
        <a href="#" class="btn" onclick="window.print(); return false;">Imprimir</a>
    </div>

    <div class="documento">
        <div class="encabezado">
            <h1 class="titulo">Vista previa de resultados</h1>
            <p class="subtitulo">Protocolo #<?= h($protocolo['id_protocolo']) ?> · Correlativo: <?= h($protocolo['correlativo'] ?: 'Sin correlativo') ?></p>
        </div>

        <div class="resumen">
            <div class="card"><span class="label">Cliente</span><span class="valor"><?= h($protocolo['cliente_nombre']) ?></span></div>
            <div class="card"><span class="label">Finca</span><span class="valor"><?= h($protocolo['nombre_finca'] ?: '—') ?></span></div>
            <div class="card"><span class="label">Tipo de protocolo</span><span class="valor"><?= h($protocolo['nombre_tipo'] ?: '—') ?></span></div>
            <div class="card"><span class="label">Fecha</span><span class="valor"><?= fmtFecha($protocolo['fecha']) ?></span></div>
            <div class="card"><span class="label">Correo</span><span class="valor"><?= h($protocolo['correo'] ?: $protocolo['cliente_correo']) ?></span></div>
            <div class="card"><span class="label">Teléfono</span><span class="valor"><?= h($protocolo['telefono'] ?: $protocolo['cliente_telefono']) ?></span></div>
            <div class="card"><span class="label">Departamento / Municipio</span><span class="valor"><?= h(trim(($protocolo['departamento'] ?: '') . ' / ' . ($protocolo['municipio'] ?: ''), ' /')) ?: '—' ?></span></div>
            <div class="card"><span class="label">Estado / avance</span><span class="valor"><?= h($totalResultados) ?> de <?= h($totalAnalisis) ?> resultados ingresados</span></div>
        </div>

        <?php if (!empty($protocolo['observaciones'])): ?>
            <div class="obs-general">
                <strong>Observaciones del protocolo:</strong><br>
                <?= nl2br(h($protocolo['observaciones'])) ?>
            </div>
        <?php endif; ?>

        <div class="seccion-titulo">Resultados por muestra</div>

        <?php if (empty($muestras)): ?>
            <div class="resultado-vacio">No hay muestras o análisis asociados a este protocolo.</div>
        <?php else: ?>
            <?php foreach ($muestras as $bloque): ?>
                <?php $m = $bloque['muestra']; ?>
                <div class="muestra-box">
                    <div class="muestra-head">
                        <h3><?= h($m['tipo_muestra']) ?> (ID <?= h($m['id_muestra']) ?>)</h3>
                        <div><strong>Fecha recepción:</strong> <?= fmtFechaHora($m['fecha_recepcion']) ?> &nbsp; | &nbsp; <strong>Estado:</strong> <?= h($m['estado_muestra']) ?></div>
                    </div>

                    <div class="muestra-grid">
                        <div><strong>Lote:</strong> <?= h($m['lote']) ?: '—' ?></div>
                        <div><strong>Cantidad:</strong> <?= h($m['cantidad']) ?: '—' ?></div>
                        <div><strong>Edad:</strong> <?= h($m['edad']) ?: '—' ?></div>
                        <div><strong>Variedad:</strong> <?= h($m['variedad']) ?: '—' ?></div>
                        <div><strong>Prueba solicitada:</strong> <?= h($m['prueba_solicitada']) ?: '—' ?></div>
                        <div><strong>Tipo vacuna:</strong> <?= h($m['tipo_vacuna']) ?: '—' ?></div>
                        <div><strong>Marca vacuna:</strong> <?= h($m['marca_vacuna']) ?: '—' ?></div>
                        <div><strong>Dosis:</strong> <?= h($m['dosis']) ?: '—' ?></div>
                    </div>

                    <?php foreach ($bloque['analisis'] as $fila): ?>
                        <div class="analisis-item">
                            <div class="analisis-head">
                                <div>
                                    <strong><?= h($fila['nombre_estudio']) ?></strong>
                                    <div style="color:#667; margin-top:4px;">Tipo formulario: <?= h($fila['tipo_formulario'] ?: 'generico') ?></div>
                                </div>
                                <div>
                                    <?php if (!empty($fila['id_resultado'])): ?>
                                        <span class="badge">Ingresado</span>
                                    <?php else: ?>
                                        <span class="badge pendiente">Pendiente</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?= renderResultado($fila) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
