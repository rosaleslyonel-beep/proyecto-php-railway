<?php
$stmt = $conexion->prepare("SELECT * FROM resultados_analisis WHERE id_muestra = ? AND id_analisis = ?");
$stmt->execute([$id_muestra, $id_analisis]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

$placas = json_decode($resultado['datos_json'] ?? '{}', true);

function calcularResumenHI($placas) {
    $total = 0;
    $cantidad = 0;
    if (!is_array($placas)) {
        return ['total' => 0, 'cantidad' => 0, 'promedio' => 0];
    }
    foreach ($placas as $placa) {
        if (!is_array($placa)) continue;
        foreach ($placa as $valor) {
            $valor = trim((string)$valor);
            if ($valor === '') continue;
            if (is_numeric($valor)) {
                $numero = (float)$valor;
                $total += $numero;
                $cantidad++;
            }
        }
    }
    return ['total' => $total, 'cantidad' => $cantidad, 'promedio' => $cantidad > 0 ? $total / $cantidad : 0];
}
$resumen_hi = calcularResumenHI($placas);

$stmt = $conexion->prepare("SELECT id_protocolo FROM muestras WHERE id_muestra = ?");
$stmt->execute([$id_muestra]);
$id_protocolo = $stmt->fetchColumn();

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
<div id="contenido" style="font-family: Arial, sans-serif;">
    <h2 style="text-align:center;">INHIBICIÓN DE LA HEMOAGLUTINACIÓN (HI)</h2>
    <div class="panel-formulario">
        <a href="gestion_protocolos.php?id=<?= $id_protocolo ?>&tab=tab_resultados" class="btn-regresar">← Regresar a Resultados</a>

        <?php if ($soloLectura): ?>
            <div style="background:#e2e3e5; border:1px solid #c6c7c8; color:#41464b; padding:12px; margin:15px 0; border-radius:6px;">
                Este resultado ya fue emitido en un informe. Solo está disponible para consulta.
                Para modificarlo, debe crear una corrección.
            </div>
        <?php endif; ?>

        <form method="post" action="guardar_resultado_hi.php?id_muestra=<?php echo $_GET['id_muestra'];?>&id_analisis=<?php echo $_GET['id_analisis'];?>">
            <div class="campo">
                <label>No. de Lote del Antígeno/Antisuero:
                    <input type="text" name="lote_antigeno" value="<?= htmlspecialchars($resultado['lote_antigeno'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>><br>
                </label><br>
            </div>
            <div class="campo">
                <label>Fecha de elaboración:
                    <input type="date" name="fecha_elaboracion" value="<?= htmlspecialchars($resultado['fecha_elaboracion'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>><br>
                </label><br>
            </div>
            <div class="campo">
                <label>Prueba para:
                    <input type="text" name="prueba_para" value="<?= htmlspecialchars($resultado['prueba_para'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>><br>
                </label><br>
            </div>
            <div class="campo">
                <label>Hora de inicio:
                    <input type="time" name="hora_inicio" value="<?= htmlspecialchars($resultado['hora_inicio'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>><br>
                </label>
            </div>
            <div class="campo">
                <label>Hora de finalización:
                    <input type="time" name="hora_fin" value="<?= htmlspecialchars($resultado['hora_fin'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
                </label><br>
            </div>
            <div class="campo">
                <label>Responsable:
                    <input type="text" name="responsable" value="<?= htmlspecialchars($resultado['responsable'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
                </label><br>
            </div>
            <div class="campo">
                <label>No. Lote control positivo (CP):
                    <input type="text" name="lote_cp" value="<?= htmlspecialchars($resultado['lote_cp'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
                </label>
            </div>
            <div class="campo">
                <label>Resultado CP:
                    <input type="text" name="resultado_cp" value="<?= htmlspecialchars($resultado['resultado_cp'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
                </label><br>
            </div>
            <div class="campo">
                <label>No. Lote control negativo (CN):
                    <input type="text" name="lote_cn" value="<?= htmlspecialchars($resultado['lote_cn'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
                </label>
            </div>
            <div class="campo">
                <label>Resultado CN:
                    <input type="text" name="resultado_cn" value="<?= htmlspecialchars($resultado['resultado_cn'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
                </label><br><br>
            </div>
            <div class="campo campo-completo">
                <style>
                    .tabla-placa input { width: 60px; text-align: center; }
                    .tabla-placa td { padding: 4px; }
                </style>

                <div id="placas-container">
                    <div class="placa" data-index="1">
                        <?php $noplaca = 0; foreach ((is_array($placas) ? $placas : []) as $placa) { $noplaca = $noplaca + 1; ?>
                            <h5>Placa <?php echo $noplaca; ?></h5>
                            <table class="tabla-placa" border="1" cellpadding="4" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <?php for ($i = 1; $i <= 12; $i++) echo "<th>$i</th>"; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                                    foreach ($letras as $letra) {
                                        echo "<tr><th>$letra</th>";
                                        for ($col = 1; $col <= 12; $col++) {
                                            $key = $letra . $col;
                                            $valor = htmlspecialchars($placa[$key] ?? '');
                                            echo "<td><input type='text' name='placas[".$noplaca ."][$key]' value='$valor' ".($soloLectura ? "readonly" : "")."></td>";
                                        }
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        <?php } ?>
                    </div>
                </div>

                <?php if (!$soloLectura): ?>
                    <button type="button" onclick="agregarPlaca(<?php echo $noplaca; ?>)">+ Agregar Placa</button>
                <?php endif; ?>
            </div>

            <div class="campo campo-completo">
                <h4>Resumen de valores</h4>
                <div id="resumen-hi" style="display:flex; gap:20px; flex-wrap:wrap; background:#f5f5f5; padding:12px; border-radius:6px;">
                    <div><strong>Total:</strong> <span id="hi-total"><?= number_format($resumen_hi['total'], 2) ?></span></div>
                    <div><strong>Cantidad de valores:</strong> <span id="hi-cantidad"><?= (int)$resumen_hi['cantidad'] ?></span></div>
                    <div><strong>Promedio:</strong> <span id="hi-promedio"><?= number_format($resumen_hi['promedio'], 2) ?></span></div>
                </div>
            </div>

            <div class="campo campo-completo">
                <label>Observaciones:</label><br>
                <textarea name="observaciones" rows="4" cols="50" <?= $soloLectura ? 'readonly' : '' ?>><?= htmlspecialchars($resultado['observaciones'] ?? '') ?></textarea><br><br>
            </div>

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

<script>
function agregarPlaca(contadorPlacas) {
    contadorPlacas++;
    const contenedor = document.getElementById("placas-container");
    const div = document.createElement("div");
    div.className = "placa";
    div.innerHTML = `
        <h5>Placa ${contadorPlacas}</h5>
        <table class="tabla-placa" border="1" cellpadding="4" cellspacing="0">
            <thead>
                <tr>
                    <th></th>
                    ${Array.from({ length: 12 }, (_, i) => `<th>${i + 1}</th>`).join("")}
                </tr>
            </thead>
            <tbody>
                ${["A","B","C","D","E","F","G","H"].map(letra =>
                    `<tr><th>${letra}</th>` +
                    Array.from({ length: 12 }, (_, i) =>
                        `<td><input type='text' name='placas[${contadorPlacas}][${letra + (i + 1)}]'></td>`).join("") +
                    "</tr>"
                ).join("")}
            </tbody>
        </table>
    `;
    contenedor.appendChild(div);
    aplicarNavegacionVertical();
    recalcularResumenHI();
}

function aplicarNavegacionVertical() {
    document.querySelectorAll('.tabla-placa input').forEach(input => {
        input.removeEventListener('keydown', manejarTab);
        input.addEventListener('keydown', manejarTab);
    });
}

function manejarTab(e) {
    if (e.key === 'Tab') {
        const cell = this.closest('td');
        const row = cell.closest('tr');
        const colIndex = Array.from(row.children).indexOf(cell);
        const tbody = row.parentElement;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const currentRowIndex = rows.indexOf(row);
        const nextRow = rows[currentRowIndex + 1];

        if (nextRow && nextRow.children[colIndex]) {
            const nextInput = nextRow.children[colIndex].querySelector('input');
            if (nextInput) {
                e.preventDefault();
                nextInput.focus();
            }
        } else {
            if (colIndex + 1 < row.children.length) {
                const nextInput = rows[0].children[colIndex + 1].querySelector('input');
                if (nextInput) {
                    e.preventDefault();
                    nextInput.focus();
                }
            }
        }
    }
}

document.addEventListener("DOMContentLoaded", aplicarNavegacionVertical);

function recalcularResumenHI() {
    const inputs = document.querySelectorAll('.tabla-placa input');
    let total = 0;
    let cantidad = 0;

    inputs.forEach(input => {
        const valor = input.value.trim();
        if (valor !== '' && !isNaN(valor)) {
            total += parseFloat(valor);
            cantidad++;
        }
    });

    const promedio = cantidad > 0 ? (total / cantidad) : 0;
    const totalEl = document.getElementById('hi-total');
    const cantidadEl = document.getElementById('hi-cantidad');
    const promedioEl = document.getElementById('hi-promedio');

    if (totalEl) totalEl.textContent = total.toFixed(2);
    if (cantidadEl) cantidadEl.textContent = cantidad;
    if (promedioEl) promedioEl.textContent = promedio.toFixed(2);
}

document.addEventListener('input', function(e) {
    if (e.target.matches('.tabla-placa input')) {
        recalcularResumenHI();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    recalcularResumenHI();
});
</script>
