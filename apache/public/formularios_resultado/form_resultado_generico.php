<?php
// Obtener resultado existente
$stmt = $conexion->prepare("SELECT * FROM resultados_analisis WHERE id_muestra = ? AND id_analisis = ?");
$stmt->execute([$id_muestra, $id_analisis]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener id_protocolo
$stmt = $conexion->prepare("SELECT id_protocolo FROM muestras WHERE id_muestra = ?");
$stmt->execute([$id_muestra]);
$id_protocolo = $stmt->fetchColumn();

$datos = json_decode($resultado['datos_json'] ?? '{}', true);
$archivo = $datos['archivo'] ?? null;
?>

<div id="contenido" style="font-family: Arial;">
    <div class="panel-formulario">

        <a href="gestion_protocolos.php?id=<?= $id_protocolo ?>&tab=tab_resultados" class="btn-regresar">
            ← Regresar a Resultados
        </a>

        <form method="post"
              action="guardar_resultado_generico.php?id_muestra=<?= $id_muestra ?>&id_analisis=<?= $id_analisis ?>"
              enctype="multipart/form-data">

            <div class="campo campo-completo">
                <label>Observaciones:</label><br>
                <textarea name="observaciones" rows="4" style="width:100%;"><?= htmlspecialchars($resultado['observaciones'] ?? '') ?></textarea>
            </div>

            <div class="campo campo-completo">
                <label>Adjuntar archivo:</label><br>
                <input type="file" name="archivo">
            </div>

            <?php if ($archivo): ?>
                <div class="campo campo-completo">
                    <label>Archivo actual:</label><br>
                    <a href="uploads/resultados/<?= htmlspecialchars($archivo) ?>" target="_blank">
                        Ver archivo adjunto
                    </a>
                </div>
            <?php endif; ?>

            <div class="campo campo-completo">
                <button type="submit">Guardar Resultado</button>
            </div>

        </form>
    </div>
</div>