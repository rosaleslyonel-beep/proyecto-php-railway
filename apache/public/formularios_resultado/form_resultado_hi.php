
<?php
// Cargar resultado existente (si existe)
$stmt = $conexion->prepare("SELECT * FROM resultados_analisis WHERE id_muestra = ? AND id_analisis = ?");
$stmt->execute([$id_muestra, $id_analisis]);
$resultado = $stmt->fetch();
 
$placas = json_decode($resultado['datos_json'] ?? '{}', true);

// Obtener id_protocolo desde la muestra si no se tiene
$stmt = $conexion->prepare("SELECT id_protocolo FROM muestras WHERE id_muestra = ?");
$stmt->execute([$id_muestra]);
$id_protocolo = $stmt->fetchColumn();
?>

<div id="contenido"  style="font-family: Arial, sans-serif;">
    <h2 style="text-align:center;">INHIBICIÓN DE LA HEMOAGLUTINACIÓN (HI)</h2>
    <div class="panel-formulario">
          <a href="gestion_protocolos.php?id=<?= $id_protocolo ?>&tab=tab_resultados" class="btn-regresar">← Regresar a Resultados</a>

        <form method="post" action="guardar_resultado_hi.php?id_muestra=<?php echo $_GET['id_muestra'];?>&id_analisis=<?php echo $_GET['id_analisis'];?>">
            <div class="campo">
                <label>No. de Lote del Antígeno/Antisuero:   <input type="text" name="lote_antigeno" value="<?= htmlspecialchars($resultado['lote_antigeno'] ?? '') ?>"><br></label><br>
            </div>
            <div class="campo">
                <label>Fecha de elaboración:   <input type="date" name="fecha_elaboracion" value="<?= htmlspecialchars($resultado['fecha_elaboracion'] ?? '') ?>"><br></label><br>
            </div>
            <div class="campo">
            <label>Prueba para:  <input type="text" name="prueba_para" value="<?= htmlspecialchars($resultado['prueba_para'] ?? '') ?>"><br>
</label><br>
            </div>
            <div class="campo">
            <label>Hora de inicio:  <input type="time" name="hora_inicio" value="<?= htmlspecialchars($resultado['hora_inicio'] ?? '') ?>"><br>
</label>
            </div>
            <div class="campo">
            <label>Hora de finalización:  <input type="time" name="hora_fin" value="<?= htmlspecialchars($resultado['hora_fin'] ?? '') ?>"></label><br>
            </div>
            <div class="campo">
            <label>Responsable: <input type="text" name="responsable" value="<?= htmlspecialchars($resultado['responsable'] ?? '') ?>"></label><br>
            </div>
            <div class="campo">
            <label>No. Lote control positivo (CP):    <input type="text" name="lote_cp" value="<?= htmlspecialchars($resultado['lote_cp'] ?? '') ?>"></label>
            </div>
            <div class="campo">
            <label>Resultado CP:  <input type="text" name="resultado_cp" value="<?= htmlspecialchars($resultado['resultado_cp'] ?? '') ?>"></label><br>
            </div>
            <div class="campo">
            <label>No. Lote control negativo (CN):    <input type="text" name="lote_cn" value="<?= htmlspecialchars($resultado['lote_cn'] ?? '') ?>"></label>
            </div>
            <div class="campo">
            <label>Resultado CN:  <input type="text" name="resultado_cn" value="<?= htmlspecialchars($resultado['resultado_cn'] ?? '') ?>"></label><br><br>
            </div>
            <div class="campo campo-completo">
                <style>
        .tabla-placa input {
            width: 60px;
            text-align: center;
        }
        .tabla-placa td {
            padding: 4px;
        }
    </style>

<div id="placas-container"> 

        <div class="placa" data-index="1">
              <?php  $noplaca = 0; foreach ($placas as $placa) { 
                $noplaca = $noplaca+1;
                ?>
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
                            echo "<td><input type='text' name='placas[".$noplaca ."][$key]' value='$valor'></td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
           <?php    } ?>
        </div>  
   </div>             
    <button type="button" onclick="agregarPlaca(<?php echo $noplaca; ?>)">+ Agregar Placa</button>
            </div>            
             <div class="campo campo-completo">
                <label>Observaciones:</label><br>
                <textarea name="observaciones" rows="4" cols="50"><?= htmlspecialchars($resultado['observaciones'] ?? '') ?></textarea><br><br>
            </div>            
             <div class="campo campo-completo">
                <button type="submit">Guardar Resultado</button>
            </div>
         </form>
    </div>
</div>

<script>
    //let contadorPlacas = 1;

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
            // Si estamos en la última fila, saltamos a la primera fila de la siguiente columna
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
    // Navegación vertical con Tab
    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll('.tabla-placa input').forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    const cell = this.closest('td');
                    const row = cell.closest('tr');
                    const colIndex = Array.from(row.children).indexOf(cell);
                    const nextRow = row.nextElementSibling;
                    if (nextRow && nextRow.children[colIndex]) {
                        const nextInput = nextRow.children[colIndex].querySelector('input');
                        if (nextInput) {
                            e.preventDefault();
                            nextInput.focus();
                        }
                    }
                }
            });
        });
    });
</script>