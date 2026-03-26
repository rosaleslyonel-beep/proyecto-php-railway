<?php
// Tab de resultados de análisis por muestra dentro del protocolo
$estadoProtocolo = $protocolo['estado'] ?? 'BORRADOR';
$soloConsultaResultados = ($estadoProtocolo === 'CERRADO');

// Obtener muestras del protocolo
$stmt = $conexion->prepare("SELECT id_muestra, tipo_muestra FROM muestras WHERE id_protocolo = ? ORDER BY id_muestra ASC");
$stmt->execute([$id_protocolo]);
$muestras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Validar si todos los análisis del protocolo ya tienen resultado
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
$resumenResultados = $stmtTotales->fetch(PDO::FETCH_ASSOC);

$totalAnalisisProtocolo = (int)($resumenResultados['total_analisis'] ?? 0);
$totalResultadosProtocolo = (int)($resumenResultados['total_resultados'] ?? 0);
$resultadosCompletos = $totalAnalisisProtocolo > 0 && $totalAnalisisProtocolo === $totalResultadosProtocolo;

// Historial de emisiones
$stmtEmisiones = $conexion->prepare("
    SELECT 
        id_emision,
        fecha_emision,
        tipo_emision,
        correlativo_emitido,
        observaciones,
        id_protocolo_destino
    FROM protocolo_emisiones_resultados
    WHERE id_protocolo = ?
    ORDER BY fecha_emision DESC, id_emision DESC
");
$stmtEmisiones->execute([$id_protocolo]);
$emisiones = $stmtEmisiones->fetchAll(PDO::FETCH_ASSOC);

// Protocolos hijos / relacionados
$stmtHijos = $conexion->prepare("
    SELECT 
        id_protocolo,
        correlativo,
        estado,
        tipo_derivacion,
        created_date
    FROM protocolos
    WHERE id_protocolo_padre = ?
    ORDER BY no_derivacion ASC, id_protocolo ASC
");
$stmtHijos->execute([$id_protocolo]);
$protocolosHijos = $stmtHijos->fetchAll(PDO::FETCH_ASSOC);

// Protocolo padre si aplica
$protocoloPadre = null;
if (!empty($protocolo['id_protocolo_padre'])) {
    $stmtPadre = $conexion->prepare("
        SELECT id_protocolo, correlativo, estado, tipo_derivacion
        FROM protocolos
        WHERE id_protocolo = ?
    ");
    $stmtPadre->execute([$protocolo['id_protocolo_padre']]);
    $protocoloPadre = $stmtPadre->fetch(PDO::FETCH_ASSOC);
}
?>

<h3>📋 Resultados por Muestra</h3>

<?php if (!empty($_GET['id_hijo'])): ?>
    <div style="background:#e8f5e9; border:1px solid #a5d6a7; color:#1b5e20; padding:12px; margin-bottom:15px; border-radius:4px;">
        Se creó un protocolo derivado:
        <a href="gestion_protocolos.php?id=<?= (int)$_GET['id_hijo'] ?>&tab=tab_resultados" style="font-weight:bold; color:#1b5e20;">
            abrir protocolo #<?= (int)$_GET['id_hijo'] ?>
        </a>
    </div>
<?php endif; ?>

<?php if ($estadoProtocolo === 'BORRADOR'): ?>
    <div style="background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding:12px; margin-bottom:15px; border-radius:4px;">
        Este protocolo aún está en <strong>BORRADOR</strong>. El ingreso de resultados debe realizarse cuando tenga correlativo y pase a <strong>PENDIENTE_RESULTADOS</strong>.
    </div>
<?php elseif ($soloConsultaResultados): ?>
    <div style="background:#e2e3e5; border:1px solid #c6c7c8; color:#41464b; padding:12px; margin-bottom:15px; border-radius:4px;">
        Este protocolo está <strong>CERRADO</strong>. Los resultados quedan disponibles solo para consulta.
    </div>
<?php endif; ?>

<div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:16px;">
    <div style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:10px 12px; min-width:240px;">
        <strong>Resumen del protocolo</strong><br>
        <span>Total de análisis: <strong><?= $totalAnalisisProtocolo ?></strong></span><br>
        <span>Resultados ingresados: <strong><?= $totalResultadosProtocolo ?></strong></span>
    </div>

    <?php if ($totalResultadosProtocolo > 0): ?>
        <a href="vista_previa_resultados.php?id_protocolo=<?= $id_protocolo ?>" target="_blank"
           style="display:inline-block; background:#0d6efd; color:#fff; text-decoration:none; padding:10px 14px; border-radius:6px; font-weight:bold;">
            👁️ Vista previa de resultados
        </a>

        <?php if ($estadoProtocolo !== 'CERRADO'): ?>
            <a href="generar_resultados.php?id_protocolo=<?= $id_protocolo ?>"
               onclick="return confirm('¿Desea generar la emisión de resultados de este protocolo? Si hay análisis pendientes, se creará un protocolo de seguimiento.');"
               style="display:inline-block; background:#198754; color:#fff; text-decoration:none; padding:10px 14px; border-radius:6px; font-weight:bold;">
                📤 Generar resultados
            </a>
        <?php endif; ?>

        <a href="seleccionar_correccion.php?id_protocolo=<?= $id_protocolo ?>"
           style="display:inline-block; background:#fd7e14; color:#fff; text-decoration:none; padding:10px 14px; border-radius:6px; font-weight:bold;">
            ♻️ Crear corrección
        </a>
    <?php elseif ($totalAnalisisProtocolo > 0): ?>
        <div style="background:#e7f1ff; border:1px solid #b6d4fe; color:#084298; padding:10px 12px; border-radius:6px;">
            La vista previa y la generación de resultados se habilitan cuando exista al menos un resultado ingresado.
        </div>
    <?php endif; ?>
</div>

<?php if ($protocoloPadre || count($protocolosHijos) > 0): ?>
    <div style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:8px; padding:14px; margin-bottom:18px;">
        <h4 style="margin-top:0;">🔗 Protocolos relacionados</h4>

        <?php if ($protocoloPadre): ?>
            <div style="margin-bottom:10px;">
                <strong>Protocolo origen:</strong>
                <a href="gestion_protocolos.php?id=<?= (int)$protocoloPadre['id_protocolo'] ?>&tab=tab_resultados">
                    <?= htmlspecialchars($protocoloPadre['correlativo'] ?: ('ID ' . $protocoloPadre['id_protocolo'])) ?>
                </a>
                <span style="color:#6c757d;">(<?= htmlspecialchars($protocoloPadre['tipo_derivacion'] ?: 'ORIGINAL') ?> · <?= htmlspecialchars($protocoloPadre['estado'] ?: '') ?>)</span>
            </div>
        <?php endif; ?>

        <?php if (count($protocolosHijos) > 0): ?>
            <div><strong>Protocolos derivados:</strong></div>
            <ul style="margin-top:8px; margin-bottom:0; padding-left:20px;">
                <?php foreach ($protocolosHijos as $hijo): ?>
                    <li style="margin-bottom:6px;">
                        <a href="gestion_protocolos.php?id=<?= (int)$hijo['id_protocolo'] ?>&tab=tab_resultados">
                            <?= htmlspecialchars($hijo['correlativo'] ?: ('ID ' . $hijo['id_protocolo'])) ?>
                        </a>
                        <span style="color:#6c757d;">
                            (<?= htmlspecialchars($hijo['tipo_derivacion'] ?: '') ?> · <?= htmlspecialchars($hijo['estado'] ?: '') ?>
                            <?php if (!empty($hijo['created_date'])): ?>
                                · <?= htmlspecialchars(date('d/m/Y H:i', strtotime($hijo['created_date']))) ?>
                            <?php endif; ?>)
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (count($emisiones) > 0): ?>
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:14px; margin-bottom:18px;">
        <h4 style="margin-top:0;">🧾 Resultados generados</h4>
        <table class="tabla" border="1" width="100%" cellpadding="6" cellspacing="0">
            <thead style="background:#f8f9fa;">
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Correlativo</th>
                    <th>Observaciones</th>
                    <th>Destino</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emisiones as $emision): ?>
                    <tr>
                        <td><?= !empty($emision['fecha_emision']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($emision['fecha_emision']))) : '—' ?></td>
                        <td><?= htmlspecialchars($emision['tipo_emision']) ?></td>
                        <td><?= htmlspecialchars($emision['correlativo_emitido'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($emision['observaciones'] ?: '—') ?></td>
                        <td>
                            <?php if (!empty($emision['id_protocolo_destino'])): ?>
                                <a href="gestion_protocolos.php?id=<?= (int)$emision['id_protocolo_destino'] ?>&tab=tab_resultados">
                                    Protocolo #<?= (int)$emision['id_protocolo_destino'] ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="vista_previa_resultados.php?id_protocolo=<?= $id_protocolo ?>&id_emision=<?= (int)$emision['id_emision'] ?>"
                               target="_blank" class="btn btn-sm">
                                👁️ Ver emisión
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if (count($muestras) === 0): ?>
    <p>No hay muestras registradas en este protocolo.</p>
<?php else: ?>
    <?php foreach ($muestras as $muestra): ?>
        <h4>Muestra: <?= htmlspecialchars($muestra['tipo_muestra']) ?> (ID <?= $muestra['id_muestra'] ?>)</h4>
        <table class="tabla" border="1" width="100%" cellpadding="4" cellspacing="0">
            <thead>
                <tr>
                    <th>Análisis</th>
                    <th>Estado</th>
                    <th>Traslado / corrección</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmtAnalisis = $conexion->prepare("
                SELECT
                    a.id_analisis,
                    a.nombre_estudio,
                    COALESCE(ma.estado_resultado, 'ACTIVO') AS estado_resultado,
                    ma.id_protocolo_destino,
                    ma.observacion_traslado,
                    COALESCE((
                        SELECT COUNT(*)
                        FROM resultados_analisis r
                        WHERE r.id_analisis = a.id_analisis
                          AND r.id_muestra = ?
                    ), 0) AS tiene_resultado
                FROM muestra_analisis ma
                JOIN analisis_laboratorio a ON ma.id_analisis = a.id_analisis
                WHERE ma.id_muestra = ?
                ORDER BY a.nombre_estudio ASC
            ");
            $stmtAnalisis->execute([$muestra['id_muestra'], $muestra['id_muestra']]);
            $analisis = $stmtAnalisis->fetchAll(PDO::FETCH_ASSOC);

            foreach ($analisis as $a):
                if (($a['estado_resultado'] ?? 'ACTIVO') === 'TRASLADADO') {
                    $estado = '📦 Trasladado';
                } elseif (($a['estado_resultado'] ?? 'ACTIVO') === 'CORREGIDO') {
                    $estado = '♻️ Corregido';
                } else {
                    $estado = $a['tiene_resultado'] ? '✅ Ingresado' : '🕗 Pendiente';
                }

                $textoAccion = $soloConsultaResultados
                    ? '🔍 Ver'
                    : ($a['tiene_resultado'] ? '🔍 Ver / Editar' : '✏️ Ingresar Resultado');
            ?>
                <tr>
                    <td><?= htmlspecialchars($a['nombre_estudio']) ?></td>
                    <td><?= $estado ?></td>
                    <td>
                        <?php if (!empty($a['id_protocolo_destino'])): ?>
                            <a href="gestion_protocolos.php?id=<?= (int)$a['id_protocolo_destino'] ?>&tab=tab_resultados">
                                Ir al protocolo #<?= (int)$a['id_protocolo_destino'] ?>
                            </a>
                            <?php if (!empty($a['observacion_traslado'])): ?>
                                <br><small><?= htmlspecialchars($a['observacion_traslado']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= !empty($a['observacion_traslado']) ? htmlspecialchars($a['observacion_traslado']) : '—' ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="resultado_analisis.php?id_protocolo=<?= $id_protocolo ?>&id_muestra=<?= $muestra['id_muestra'] ?>&id_analisis=<?= $a['id_analisis'] ?>" class="btn btn-sm">
                            <?= $textoAccion ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <br>
    <?php endforeach; ?>
<?php endif; ?>
