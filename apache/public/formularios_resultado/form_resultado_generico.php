<?php
$stmt = $conexion->prepare("SELECT * FROM resultados_analisis WHERE id_muestra = ? AND id_analisis = ?");
$stmt->execute([$id_muestra, $id_analisis]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conexion->prepare("SELECT id_protocolo FROM muestras WHERE id_muestra = ?");
$stmt->execute([$id_muestra]);
$id_protocolo = $stmt->fetchColumn();

$datos = json_decode($resultado['datos_json'] ?? '{}', true);
$archivo = $datos['archivo'] ?? null;

$id_resultado_actual = $resultado['id_resultado'] ?? null;
$resultado_emitido = false;

if ($id_resultado_actual && $id_protocolo) {
    $stmt = $conexion->prepare("SELECT resultados_incluidos_json FROM protocolo_emisiones_resultados WHERE id_protocolo = ?");
    $stmt->execute([$id_protocolo]);
    $emisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($emisiones as $emision) {
        $ids = json_decode($emision['resultados_incluidos_json'] ?? '[]', true);
        if (is_array($ids) && in_array((int)$id_resultado_actual, array_map('intval', $ids), true)) {
            $resultado_emitido = true;
            break;
        }
    }
}
$soloLectura = $resultado_emitido;
?>
<div id="contenido" style="font-family: Arial;">
    <div class="panel-formulario">
        <a href="gestion_protocolos.php?id=<?= $id_protocolo ?>&tab=tab_resultados" class="btn-regresar">← Regresar a Resultados</a>

        <?php if ($soloLectura): ?>
            <div style="background:#e2e3e5; border:1px solid #c6c7c8; color:#41464b; padding:12px; margin:15px 0; border-radius:6px;">
                Este resultado ya fue emitido en un informe. Solo está disponible para consulta.
                Para modificarlo, debe crear una corrección.
            </div>
        <?php endif; ?>

        <form method="post" action="guardar_resultado_generico.php?id_muestra=<?= $id_muestra ?>&id_analisis=<?= $id_analisis ?>" enctype="multipart/form-data">
            <div class="campo campo-completo">
                <label>Observaciones:</label><br>
                <textarea name="observaciones" rows="4" style="width:100%;" <?= $soloLectura ? 'readonly' : '' ?>><?= htmlspecialchars($resultado['observaciones'] ?? '') ?></textarea>
            </div>

            <div class="campo campo-completo">
                <label>Adjuntar archivo:</label><br>
                <?php if ($soloLectura): ?>
                    <div style="background:#f8f9fa; border:1px solid #dee2e6; padding:10px; border-radius:6px;">
                        La carga de archivos está deshabilitada porque este resultado ya fue emitido.
                    </div>
                <?php else: ?>
                    <input type="file" name="archivo">
                <?php endif; ?>
            </div>

            <?php if ($archivo): ?>
                <div class="campo campo-completo">
                    <label>Archivo actual:</label><br>
                    <a href="ver_resultado_adjunto.php?file=<?= urlencode($archivo) ?>" target="_blank">Ver archivo adjunto</a>
                </div>
            <?php endif; ?>

            <div class="campo campo-completo">
                <?php if ($soloLectura): ?>
                    <div style="background:#e2e3e5; border:1px solid #c6c7c8; color:#41464b; padding:10px 12px; border-radius:6px;">
                        Este resultado ya fue emitido. Para modificarlo, debe crear una corrección.
                    </div>
                <?php else: ?>
                    <button type="submit">Guardar Resultado</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
