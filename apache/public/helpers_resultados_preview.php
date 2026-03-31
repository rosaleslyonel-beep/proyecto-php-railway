<?php
if (!function_exists('rp_h')) {
    function rp_h($valor): string {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rp_fmt_fecha')) {
    function rp_fmt_fecha($valor): string {
        if (!$valor) return '—';
        $ts = strtotime((string)$valor);
        return $ts ? date('d/m/Y', $ts) : rp_h($valor);
    }
}

if (!function_exists('rp_fmt_fecha_hora')) {
    function rp_fmt_fecha_hora($valor): string {
        if (!$valor) return '—';
        $ts = strtotime((string)$valor);
        return $ts ? date('d/m/Y H:i', $ts) : rp_h($valor);
    }
}

if (!function_exists('rp_adjunto_url')) {
    function rp_adjunto_url(?string $archivo): string {
        $archivo = basename((string)$archivo);
        return 'ver_resultado_adjunto.php?file=' . rawurlencode($archivo);
    }
}

if (!function_exists('rp_render_adjunto_inline')) {
    function rp_render_adjunto_inline(?string $archivo): string {
        if (!$archivo) {
            return '';
        }
        $archivo = basename((string)$archivo);
        $url = rp_adjunto_url($archivo);
        $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return '
                <div class="adjunto-box">
                    <div class="adjunto-label">Adjunto:</div>
                    <img src="' . rp_h($url) . '" alt="Adjunto" class="adjunto-img">
                    <div class="adjunto-link">
                        <a href="' . rp_h($url) . '" target="_blank">Abrir archivo</a>
                    </div>
                </div>
            ';
        }

        if ($ext === 'pdf') {
            return '
                <div class="adjunto-box">
                    <div class="adjunto-label">Adjunto:</div>
                    <iframe src="' . rp_h($url) . '" class="adjunto-pdf"></iframe>
                    <div class="adjunto-link">
                        <a href="' . rp_h($url) . '" target="_blank">Abrir PDF en otra pestaña</a>
                    </div>
                </div>
            ';
        }

        return '
            <div class="adjunto-box">
                <div class="adjunto-label">Adjunto:</div>
                <div class="adjunto-link">
                    <a href="' . rp_h($url) . '" target="_blank">' . rp_h($archivo) . '</a>
                </div>
            </div>
        ';
    }
}

if (!function_exists('rp_obtener_datos_vista_previa')) {
    function rp_obtener_datos_vista_previa(PDO $conexion, int $id_protocolo, ?array $resultadoIds = null): array {
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
            throw new RuntimeException('No se encontró el protocolo.');
        }

        $stmt = $conexion->prepare("
            SELECT 
                m.*,
                ma.id_analisis,
                ma.precio_unitario,
                ma.cantidad AS cantidad_analisis,
                ma.estado_resultado,
                ma.id_protocolo_destino,
                ma.observacion_traslado,
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

        if ($resultadoIds !== null) {
            $idsMap = array_fill_keys(array_map('intval', $resultadoIds), true);
            $filas = array_values(array_filter($filas, function($fila) use ($idsMap) {
                return !empty($fila['id_resultado']) && isset($idsMap[(int)$fila['id_resultado']]);
            }));
        }

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

        return [
            'protocolo' => $protocolo,
            'filas' => $filas,
            'muestras' => $muestras,
            'total_analisis' => count($filas),
            'total_resultados' => count(array_filter($filas, fn($f) => !empty($f['id_resultado']))),
        ];
    }
}

if (!function_exists('rp_render_resultado')) {
    function rp_render_resultado(array $fila): string {
        $tipo = strtolower((string)($fila['tipo_formulario'] ?? 'generico'));
        $datos = json_decode($fila['datos_json'] ?? '{}', true);
        if (!is_array($datos)) {
            $datos = [];
        }

        if (empty($fila['id_resultado'])) {
            return '<div class="resultado-vacio">Resultado no ingresado.</div>';
        }

        $html = '';
        if ($tipo === 'hi') {
            $html .= '<div class="resultado-bloque">';
            $html .= '<div class="resultado-grid">';
            $html .= '<div><strong>Lote antígeno/antisuero:</strong> ' . rp_h($fila['lote_antigeno']) . '</div>';
            $html .= '<div><strong>Fecha elaboración:</strong> ' . rp_fmt_fecha($fila['fecha_elaboracion']) . '</div>';
            $html .= '<div><strong>Prueba para:</strong> ' . rp_h($fila['prueba_para']) . '</div>';
            $html .= '<div><strong>Hora inicio:</strong> ' . rp_h($fila['hora_inicio']) . '</div>';
            $html .= '<div><strong>Hora fin:</strong> ' . rp_h($fila['hora_fin']) . '</div>';
            $html .= '<div><strong>Responsable:</strong> ' . rp_h($fila['responsable']) . '</div>';
            $html .= '<div><strong>Lote CP:</strong> ' . rp_h($fila['lote_cp']) . '</div>';
            $html .= '<div><strong>Resultado CP:</strong> ' . rp_h($fila['resultado_cp']) . '</div>';
            $html .= '<div><strong>Lote CN:</strong> ' . rp_h($fila['lote_cn']) . '</div>';
            $html .= '<div><strong>Resultado CN:</strong> ' . rp_h($fila['resultado_cn']) . '</div>';
            $html .= '</div>';

            $placas = is_array($datos) ? $datos : [];
$totalHI = 0;
$cantidadHI = 0;

foreach ($placas as $placa) {
    if (!is_array($placa)) continue;

    foreach ($placa as $valor) {
        $valor = trim((string)$valor);
        if ($valor !== '' && is_numeric($valor)) {
            $totalHI += (float)$valor;
            $cantidadHI++;
        }
    }
}

$promedioHI = $cantidadHI > 0 ? $totalHI / $cantidadHI : 0;

$html .= '<div class="resultado-grid">';
$html .= '<div><strong>Total:</strong> ' . number_format($totalHI, 2) . '</div>';
$html .= '<div><strong>Cantidad de valores:</strong> ' . $cantidadHI . '</div>';
$html .= '<div><strong>Promedio:</strong> ' . number_format($promedioHI, 2) . '</div>';
$html .= '</div>';
			 

            $html .= '<div class="obs"><strong>Observaciones:</strong><br>' . nl2br(rp_h($fila['observaciones'])) . '</div>';
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

            $html .= '<div class="resultado-bloque">';
            $html .= '<div class="resultado-grid">';
            $html .= '<div><strong>Total placas:</strong> ' . rp_h($total) . '</div>';
            $html .= '<div><strong>Positivas:</strong> ' . rp_h($positivos) . '</div>';
            $html .= '<div><strong>Negativas:</strong> ' . rp_h($negativos) . '</div>';
            $html .= '<div><strong>Fecha elaboración:</strong> ' . rp_h($datos['fecha_elaboracion'] ?? '') . '</div>';
            $html .= '<div><strong>Fecha lectura:</strong> ' . rp_h($datos['fecha_lectura'] ?? '') . '</div>';
            $html .= '<div><strong>Prueba para:</strong> ' . rp_h($datos['prueba_para'] ?? '') . '</div>';
            $html .= '<div><strong>Procesada por:</strong> ' . rp_h($datos['procesada_por'] ?? '') . '</div>';
            $html .= '<div><strong>Realizada por:</strong> ' . rp_h($datos['realizada_por'] ?? '') . '</div>';
            $html .= '</div>';
            $html .= '<div class="obs"><strong>Observaciones:</strong><br>' . nl2br(rp_h($fila['observaciones'])) . '</div>';
            $html .= '</div>';
            return $html;
        }


		  if ($tipo === 'brucella') {
            $resultado = strtolower((string)($datos['resultado'] ?? ''));
            $resultadoTexto = '—';
            if ($resultado === 'positivo') {
                $resultadoTexto = 'Positivo';
            } elseif ($resultado === 'negativo') {
                $resultadoTexto = 'Negativo';
            }

            $html .= '<div class="resultado-bloque">';
            $html .= '<div class="resultado-grid">';
            $html .= '<div><strong>Resultado:</strong> ' . rp_h($resultadoTexto) . '</div>';
            $html .= '<div><strong>Fecha:</strong> ' . rp_fmt_fecha($datos['fecha'] ?? '') . '</div>';
            $html .= '<div><strong>Lote del antígeno:</strong> ' . rp_h($datos['lote_antigeno'] ?? '') . '</div>';
            $html .= '<div><strong>Lote del antisuero:</strong> ' . rp_h($datos['lote_antisuero'] ?? '') . '</div>';
            $html .= '<div><strong>Responsable:</strong> ' . rp_h($datos['responsable'] ?? '') . '</div>';
            $html .= '<div><strong>Supervisor:</strong> ' . rp_h($datos['supervisor'] ?? '') . '</div>';
            $html .= '</div>';
            $html .= '<div class="obs"><strong>Observaciones:</strong><br>' . nl2br(rp_h($fila['observaciones'])) . '</div>';
            $html .= '</div>';
            return $html;
        }			 		 

        if ($tipo === 'prueba_rapida_placa') {
            $filas = $datos['filas'] ?? [];
            if (!is_array($filas)) {
                $filas = [];
            }

            $otraNombre = trim((string)($datos['otra_nombre'] ?? ''));
            if ($otraNombre === '') {
                $otraNombre = 'Otra';
            }

            $contar = function(array $filas, string $campo, string $valor): int {
                $total = 0;
                foreach ($filas as $filaTabla) {
                    if (($filaTabla[$campo] ?? '') === $valor) {
                        $total++;
                    }
                }
                return $total;
            };

            $html .= '<div class="resultado-bloque">';
            $html .= '<div class="resultado-grid">';
            $html .= '<div><strong>Fecha:</strong> ' . rp_fmt_fecha($datos['fecha'] ?? '') . '</div>';
            $html .= '<div><strong>Lote antígeno / antisuero:</strong> ' . rp_h($datos['lote_antigeno_antisuero'] ?? '') . '</div>';
            $html .= '<div><strong>Responsable:</strong> ' . rp_h($datos['responsable'] ?? '') . '</div>';
            $html .= '<div><strong>Supervisor:</strong> ' . rp_h($datos['supervisor'] ?? '') . '</div>';
            $html .= '</div>';

            $html .= '<div class="resultado-grid">';
            $html .= '<div><strong>MG positivos:</strong> ' . $contar($filas, 'mg', 'positivo') . '</div>';
            $html .= '<div><strong>MG negativos:</strong> ' . $contar($filas, 'mg', 'negativo') . '</div>';
            $html .= '<div><strong>MS positivos:</strong> ' . $contar($filas, 'ms', 'positivo') . '</div>';
            $html .= '<div><strong>MS negativos:</strong> ' . $contar($filas, 'ms', 'negativo') . '</div>';
            $html .= '<div><strong>Salmonella positivos:</strong> ' . $contar($filas, 'salmonella', 'positivo') . '</div>';
            $html .= '<div><strong>Salmonella negativos:</strong> ' . $contar($filas, 'salmonella', 'negativo') . '</div>';
            $html .= '<div><strong>' . rp_h($otraNombre) . ' positivos:</strong> ' . $contar($filas, 'otra', 'positivo') . '</div>';
            $html .= '<div><strong>' . rp_h($otraNombre) . ' negativos:</strong> ' . $contar($filas, 'otra', 'negativo') . '</div>';
            $html .= '</div>';

            $html .= '<div style="overflow:auto; margin-top:10px;">';
            $html .= '<table style="width:100%; border-collapse:collapse; min-width:900px;">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th colspan="9" style="border:1px solid #999; padding:8px; background:#f3f3f3; text-align:center;">RESULTADOS</th>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<th style="border:1px solid #999; padding:8px; background:#f3f3f3;"># de suero</th>';
            $html .= '<th colspan="2" style="border:1px solid #999; padding:8px; background:#f3f3f3;">MG</th>';
            $html .= '<th colspan="2" style="border:1px solid #999; padding:8px; background:#f3f3f3;">MS</th>';
            $html .= '<th colspan="2" style="border:1px solid #999; padding:8px; background:#f3f3f3;">Salmonella</th>';
            $html .= '<th colspan="2" style="border:1px solid #999; padding:8px; background:#f3f3f3;">' . rp_h($otraNombre) . '</th>';
            $html .= '</tr>';
            $html .= '<tr>';
            foreach (['', 'Positivo', 'Negativo', 'Positivo', 'Negativo', 'Positivo', 'Negativo', 'Positivo', 'Negativo'] as $encabezado) {
                $html .= '<th style="border:1px solid #999; padding:8px; background:#f9f9f9; text-align:center;">' . rp_h($encabezado) . '</th>';
            }
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            if (!empty($filas)) {
                ksort($filas);
                foreach ($filas as $indice => $filaTabla) {
                    $html .= '<tr>';
                    $html .= '<td style="border:1px solid #999; padding:8px; text-align:center;">' . rp_h($indice) . '</td>';
                    foreach (['mg', 'ms', 'salmonella', 'otra'] as $campo) {
                        $valor = strtolower((string)($filaTabla[$campo] ?? ''));
                        $html .= '<td style="border:1px solid #999; padding:8px; text-align:center;">' . ($valor === 'positivo' ? 'X' : '') . '</td>';
                        $html .= '<td style="border:1px solid #999; padding:8px; text-align:center;">' . ($valor === 'negativo' ? 'X' : '') . '</td>';
                    }
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr><td colspan="9" style="border:1px solid #999; padding:8px; text-align:center;">No hay filas registradas.</td></tr>';
            }

            $html .= '</tbody></table></div>';
            $html .= '<div class="obs"><strong>Observaciones:</strong><br>' . nl2br(rp_h($fila['observaciones'])) . '</div>';
            $html .= '</div>';
            return $html;
        }

        $archivo = $datos['archivo'] ?? null;
        $html .= '<div class="resultado-bloque">';
        $html .= '<div class="obs"><strong>Observaciones:</strong><br>' . nl2br(rp_h($fila['observaciones'])) . '</div>';
        if ($archivo) {
            $html .= rp_render_adjunto_inline($archivo);
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('rp_estilos_preview')) {
    function rp_estilos_preview(): string {
        return '<style>
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
        @media print { body { background: #fff; margin: 0; } .acciones { display: none; } .documento { box-shadow: none; border: none; max-width: 100%; margin: 0; padding: 0; } .muestra-box, .resultado-bloque, .card { break-inside: avoid; } }
        @media (max-width: 768px) { body { margin: 12px; } .documento { padding: 16px; } .titulo { font-size: 23px; } .adjunto-pdf { height: 380px; } }
        </style>';
    }
}

if (!function_exists('rp_construir_html_preview')) {
    function rp_construir_html_preview(PDO $conexion, int $id_protocolo, array $opciones = []): string {
        $resultadoIds = $opciones['resultado_ids'] ?? null;
        $titulo = $opciones['titulo'] ?? 'Vista previa de resultados';
        $subtituloExtra = $opciones['subtitulo_extra'] ?? '';
        $mostrarAcciones = $opciones['mostrar_acciones'] ?? true;
        $volverUrl = $opciones['volver_url'] ?? ('gestion_protocolos.php?id=' . $id_protocolo . '&tab=tab_resultados');

        $datos = rp_obtener_datos_vista_previa($conexion, $id_protocolo, $resultadoIds);
        $protocolo = $datos['protocolo'];
        $muestras = $datos['muestras'];
        $totalAnalisis = $datos['total_analisis'];
        $totalResultados = $datos['total_resultados'];

        ob_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= rp_h($titulo) ?></title>
    <?= rp_estilos_preview() ?>
</head>
<body>
    <?php if ($mostrarAcciones): ?>
    <div class="acciones">
        <a href="<?= rp_h($volverUrl) ?>" class="btn sec">← Regresar</a>
        <a href="#" class="btn" onclick="window.print(); return false;">Imprimir</a>
    </div>
    <?php endif; ?>

    <div class="documento">
        <div class="encabezado">
            <h1 class="titulo"><?= rp_h($titulo) ?></h1>
            <p class="subtitulo">
                Protocolo #<?= rp_h($protocolo['id_protocolo']) ?> · Correlativo: <?= rp_h($protocolo['correlativo'] ?: 'Sin correlativo') ?>
                <?php if ($subtituloExtra): ?> · <?= rp_h($subtituloExtra) ?><?php endif; ?>
            </p>
        </div>

        <div class="resumen">
            <div class="card"><span class="label">Cliente</span><span class="valor"><?= rp_h($protocolo['cliente_nombre']) ?></span></div>
            <div class="card"><span class="label">Finca</span><span class="valor"><?= rp_h($protocolo['nombre_finca'] ?: '—') ?></span></div>
            <div class="card"><span class="label">Tipo de protocolo</span><span class="valor"><?= rp_h($protocolo['nombre_tipo'] ?: '—') ?></span></div>
            <div class="card"><span class="label">Fecha</span><span class="valor"><?= rp_fmt_fecha($protocolo['fecha']) ?></span></div>
            <div class="card"><span class="label">Correo</span><span class="valor"><?= rp_h($protocolo['correo'] ?: $protocolo['cliente_correo']) ?></span></div>
            <div class="card"><span class="label">Teléfono</span><span class="valor"><?= rp_h($protocolo['telefono'] ?: $protocolo['cliente_telefono']) ?></span></div>
            <div class="card"><span class="label">Departamento / Municipio</span><span class="valor"><?= rp_h(trim(($protocolo['departamento'] ?: '') . ' / ' . ($protocolo['municipio'] ?: ''), ' /')) ?: '—' ?></span></div>
            <div class="card"><span class="label">Resultados incluidos</span><span class="valor"><?= rp_h($totalResultados) ?> de <?= rp_h($totalAnalisis) ?></span></div>
        </div>

        <?php if (!empty($protocolo['observaciones'])): ?>
            <div class="obs-general">
                <strong>Observaciones del protocolo:</strong><br>
                <?= nl2br(rp_h($protocolo['observaciones'])) ?>
            </div>
        <?php endif; ?>

        <div class="seccion-titulo">Resultados por muestra</div>

        <?php if (empty($muestras)): ?>
            <div class="resultado-vacio">No hay resultados disponibles para mostrar.</div>
        <?php else: ?>
            <?php foreach ($muestras as $bloque): ?>
                <?php $m = $bloque['muestra']; ?>
                <div class="muestra-box">
                    <div class="muestra-head">
                        <h3><?= rp_h($m['tipo_muestra']) ?> (ID <?= rp_h($m['id_muestra']) ?>)</h3>
                        <div><strong>Fecha recepción:</strong> <?= rp_fmt_fecha_hora($m['fecha_recepcion']) ?> &nbsp; | &nbsp; <strong>Estado:</strong> <?= rp_h($m['estado_muestra']) ?></div>
                    </div>

                    <div class="muestra-grid">
                        <div><strong>Lote:</strong> <?= rp_h($m['lote']) ?: '—' ?></div>
                        <div><strong>Cantidad:</strong> <?= rp_h($m['cantidad']) ?: '—' ?></div>
                        <div><strong>Edad:</strong> <?= rp_h($m['edad']) ?: '—' ?></div>
                        <div><strong>Variedad:</strong> <?= rp_h($m['variedad']) ?: '—' ?></div>
                        <div><strong>Prueba solicitada:</strong> <?= rp_h($m['prueba_solicitada']) ?: '—' ?></div>
                        <div><strong>Tipo vacuna:</strong> <?= rp_h($m['tipo_vacuna']) ?: '—' ?></div>
                        <div><strong>Marca vacuna:</strong> <?= rp_h($m['marca_vacuna']) ?: '—' ?></div>
                        <div><strong>Dosis:</strong> <?= rp_h($m['dosis']) ?: '—' ?></div>
                    </div>

                    <?php foreach ($bloque['analisis'] as $fila): ?>
                        <div class="analisis-item">
                            <div class="analisis-head">
                                <div>
                                    <strong><?= rp_h($fila['nombre_estudio']) ?></strong>
                                    <div style="color:#667; margin-top:4px;">Tipo formulario: <?= rp_h($fila['tipo_formulario'] ?: 'generico') ?></div>
                                </div>
                                <div><span class="badge">Incluido</span></div>
                            </div>
                            <?= rp_render_resultado($fila) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
        return ob_get_clean();
    }
}
?>