
<?php
// Tab de resultados de análisis por muestra dentro del protocolo

// Obtener muestras del protocolo
$stmt = $conexion->prepare("SELECT id_muestra, tipo_muestra FROM muestras WHERE id_protocolo = ?");
$stmt->execute([$id_protocolo]);
$muestras = $stmt->fetchAll();
?>
 
    <h3>📋 Resultados por Muestra</h3>

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
                    SELECT a.id_analisis, a.nombre_estudio,
                        (SELECT COUNT(*) FROM resultados_analisis r WHERE r.id_analisis = a.id_analisis AND r.id_muestra = ?) AS tiene_resultado
                    FROM muestra_analisis ma
                    JOIN analisis_laboratorio a ON ma.id_analisis = a.id_analisis
                    WHERE ma.id_muestra = ?
                ");
                $stmtAnalisis->execute([$muestra['id_muestra'], $muestra['id_muestra']]);
                $analisis = $stmtAnalisis->fetchAll();

                foreach ($analisis as $a):
                    $estado = $a['tiene_resultado'] ? '✅ Ingresado' : '🕗 Pendiente';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nombre_estudio']) ?></td>
                        <td><?= $estado ?></td>
                        <td>
                            <a href="resultado_analisis.php?id_protocolo=<?= $id_protocolo ?>&id_muestra=<?= $muestra['id_muestra'] ?>&id_analisis=<?= $a['id_analisis'] ?>" class="btn btn-sm">
                                <?= $a['tiene_resultado'] ? '🔍 Ver / Editar' : '✏️ Ingresar Resultado' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <br>
        <?php endforeach; ?>
    <?php endif; ?>
 