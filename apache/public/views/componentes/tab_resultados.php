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
?>

<h3>📋 Resultados por Muestra</h3>

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

        <a href="generar_resultados.php?id_protocolo=<?= $id_protocolo ?>"
           onclick="return confirm('¿Desea generar la emisión de resultados de este protocolo? Si hay análisis pendientes, se creará un protocolo de seguimiento.');"
           style="display:inline-block; background:#198754; color:#fff; text-decoration:none; padding:10px 14px; border-radius:6px; font-weight:bold;">
            📤 Generar resultados
        </a>

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
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmtAnalisis = $conexion->prepare("
                SELECT
                    a.id_analisis,
                    a.nombre_estudio,
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
                $estado = $a['tiene_resultado'] ? '✅ Ingresado' : '🕗 Pendiente';
                $textoAccion = $soloConsultaResultados
                    ? '🔍 Ver'
                    : ($a['tiene_resultado'] ? '🔍 Ver / Editar' : '✏️ Ingresar Resultado');
            ?>
                <tr>
                    <td><?= htmlspecialchars($a['nombre_estudio']) ?></td>
                    <td><?= $estado ?></td>
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
