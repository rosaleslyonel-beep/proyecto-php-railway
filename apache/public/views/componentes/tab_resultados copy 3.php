<?php
$estadoProtocolo = $protocolo['estado'] ?? 'BORRADOR';
$soloConsultaResultados = ($estadoProtocolo === 'CERRADO');

$stmt = $conexion->prepare("SELECT id_muestra, tipo_muestra FROM muestras WHERE id_protocolo = ? ORDER BY id_muestra ASC");
$stmt->execute([$id_protocolo]);
$muestras = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
      AND COALESCE(ma.estado_resultado, 'ACTIVO') = 'ACTIVO'
");
$stmtTotales->execute([$id_protocolo]);
$resumenResultados = $stmtTotales->fetch(PDO::FETCH_ASSOC);

$totalAnalisisProtocolo = (int)($resumenResultados['total_analisis'] ?? 0);
$totalResultadosProtocolo = (int)($resumenResultados['total_resultados'] ?? 0);
$resultadosCompletos = $totalAnalisisProtocolo > 0 && $totalAnalisisProtocolo === $totalResultadosProtocolo;
$tieneResultados = $totalResultadosProtocolo > 0;

$emisiones = [];
try {
    $stmtEmisiones = $conexion->prepare("
        SELECT id_emision, fecha_emision, tipo_emision, correlativo_emitido, id_protocolo_destino, observaciones
        FROM protocolo_emisiones_resultados
        WHERE id_protocolo = ?
        ORDER BY fecha_emision DESC, id_emision DESC
    ");
    $stmtEmisiones->execute([$id_protocolo]);
    $emisiones = $stmtEmisiones->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $emisiones = [];
}
?>

<h3>📋 Resultados por Muestra</h3>

<?php if (!empty($_GET['msg'])): ?>
    <div style="background:#d1e7dd; border:1px solid #badbcc; color:#0f5132; padding:12px; margin-bottom:15px; border-radius:4px;">
        <?= htmlspecialchars($_GET['msg']) ?>
        <?php if (!empty($_GET['id_hijo'])): ?>
            <br><strong>Protocolo de seguimiento creado:</strong>
            <a href="gestion_protocolos.php?id=<?= (int)$_GET['id_hijo'] ?>"><?= (int)$_GET['id_hijo'] ?></a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div style="background:#f8d7da; border:1px solid #f5c2c7; color:#842029; padding:12px; margin-bottom:15px; border-radius:4px;">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<?php if ($estadoProtocolo === 'BORRADOR'): ?>
    <div style="background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding:12px; margin-bottom:15px; border-radius:4px;">
        Este protocolo aún está en <strong>BORRADOR</strong>. El ingreso de resultados debe realizarse cuando tenga correlativo y pase a <strong>PENDIENTE_RESULTADOS</strong>.
    </div>
<?php elseif ($soloConsultaResultados): ?>
    <div style="background:#e2e3e5; border:1px solid #c6c7c8; color:#41464b; padding:12px; margin-bottom:15px; border-radius:4px;">
        Este protocolo está <strong>CERRADO</strong>. Los resultados quedan disponibles para consulta y revisiones históricas.
    </div>
<?php endif; ?>

<div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:16px;">
    <div style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:10px 12px; min-width:240px;">
        <strong>Resumen del protocolo</strong><br>
        <span>Total de análisis activos: <strong><?= $totalAnalisisProtocolo ?></strong></span><br>
        <span>Resultados ingresados: <strong><?= $totalResultadosProtocolo ?></strong></span><br>
        <span>Pendientes: <strong><?= max(0, $totalAnalisisProtocolo - $totalResultadosProtocolo) ?></strong></span>
    </div>

    <?php if ($tieneResultados): ?>
        <a href="vista_previa_resultados.php?id_protocolo=<?= $id_protocolo ?>" target="_blank"
           style="display:inline-block; background:#0d6efd; color:#fff; text-decoration:none; padding:10px 14px; border-radius:6px; font-weight:bold;">
            👁️ Vista previa
        </a>
    <?php endif; ?>

    <?php if ($tieneResultados && !$soloConsultaResultados && $estadoProtocolo !== 'BORRADOR'): ?>
        <a href="generar_resultados.php?id_protocolo=<?= $id_protocolo ?>"
           onclick="return confirm('Se generará la emisión con los resultados ya ingresados. Si existen análisis pendientes, se creará un protocolo de seguimiento con ellos. ¿Desea continuar?');"
           style="display:inline-block; background:#198754; color:#fff; text-decoration:none; padding:10px 14px; border-radius:6px; font-weight:bold;">
            📤 Generar resultados
        </a>
    <?php endif; ?>

    <?php if (!$tieneResultados): ?>
        <div style="background:#e7f1ff; border:1px solid #b6d4fe; color:#084298; padding:10px 12px; border-radius:6px;">
            La vista previa y la generación se habilitan cuando exista al menos un resultado ingresado.
        </div>
    <?php elseif ($resultadosCompletos): ?>
        <div style="background:#d1e7dd; border:1px solid #badbcc; color:#0f5132; padding:10px 12px; border-radius:6px;">
            Todos los análisis activos ya tienen resultado.
        </div>
    <?php else: ?>
        <div style="background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding:10px 12px; border-radius:6px;">
            Hay análisis pendientes. Al generar resultados se creará automáticamente un protocolo de seguimiento.
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($emisiones)): ?>
    <div style="margin-bottom:20px;">
        <h4>🕘 Historial de emisiones</h4>
        <table class="tabla" border="1" width="100%" cellpadding="4" cellspacing="0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Correlativo</th>
                    <th>Seguimiento</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emisiones as $emision): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($emision['fecha_emision']))) ?></td>
                        <td><?= htmlspecialchars($emision['tipo_emision']) ?></td>
                        <td><?= htmlspecialchars($emision['correlativo_emitido'] ?? '') ?></td>
                        <td>
                            <?php if (!empty($emision['id_protocolo_destino'])): ?>
                                <a href="gestion_protocolos.php?id=<?= (int)$emision['id_protocolo_destino'] ?>">
                                    Protocolo <?= (int)$emision['id_protocolo_destino'] ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($emision['observaciones'] ?? '') ?></td>
                        <td>
                            <a href="vista_previa_resultados.php?id_emision=<?= (int)$emision['id_emision'] ?>" target="_blank">
                                🔍 Ver emisión
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
                    <th>Traslado</th>
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
                    $estado = '↪️ Trasladado';
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
                            <a href="gestion_protocolos.php?id=<?= (int)$a['id_protocolo_destino'] ?>">
                                Ir al protocolo <?= (int)$a['id_protocolo_destino'] ?>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($a['estado_resultado'] ?? 'ACTIVO') === 'TRASLADADO'): ?>
                            <span style="color:#666;">Solo consulta</span>
                        <?php else: ?>
                            <a href="resultado_analisis.php?id_protocolo=<?= $id_protocolo ?>&id_muestra=<?= $muestra['id_muestra'] ?>&id_analisis=<?= $a['id_analisis'] ?>" class="btn btn-sm">
                                <?= $textoAccion ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <br>
    <?php endforeach; ?>
<?php endif; ?>