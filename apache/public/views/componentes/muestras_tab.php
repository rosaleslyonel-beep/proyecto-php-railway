<?php
$id_protocolo = $_GET['id'] ?? null;
$tipo_protocolo_actual = $protocolo['id_tipo_protocolo'] ;
?>

<!-- Tabla de muestras -->
<h4>Muestras Registradas</h4>
<div id="contenedor-tabla-muestras" style="height: 250px; overflow-y: auto; border: 1px solid #ccc; padding: 0px; margin-bottom: 15px;">

    <table id="tabla_muestras" style="padding: 0px;">
        <thead> 
            <tr>
                <th><input type="text" onkeyup="filtrarTabla()" placeholder="Filtrar tipo"></th>
                <th><input type="text" onkeyup="filtrarTabla()" placeholder="Filtrar lote"></th>
                <th><input type="text" onkeyup="filtrarTabla()" placeholder="Cantidad"></th>
                <th><input type="text" onkeyup="filtrarTabla()" placeholder="Edad"></th>
                <th><input type="text" onkeyup="filtrarTabla()" placeholder="Variedad"></th>
                
            
            </tr>
            <tr>
                <th>Tipo</th>
                <th>Lote</th>
                <th>Cantidad</th>
                <th>Edad</th>
                <th>Variedad</th>
                 
                
            </tr>
            
        </thead>
        <tbody>
            <?php
            $stmt = $conexion->prepare("SELECT * FROM muestras WHERE id_protocolo = ?");
            $stmt->execute([$id_protocolo]);
            while ($m = $stmt->fetch()) {
                echo "<tr data-id-muestra='{$m['id_muestra']}' onclick='seleccionarMuestra(".json_encode($m).")'>";
                echo "<td>".htmlspecialchars($m['tipo_muestra'])."</td>";
                echo "<td>".htmlspecialchars($m['lote'])."</td>";
                echo "<td>".htmlspecialchars($m['cantidad'])."</td>";
                echo "<td>".htmlspecialchars($m['edad'])."</td>";
                echo "<td>".htmlspecialchars($m['variedad'])."</td>";
                
            // echo "<td><a href='controllers/eliminar_muestra.php?id_muestra={$m['id_muestra']}&id_protocolo={$id_protocolo}' onclick=\"return confirm('¿Eliminar esta muestra?')\">🗑</a></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>
<hr>

<!-- Barra herramientas -->
<div class="barra-herramientas">
    <button type="button" onclick="nuevaMuestra()">➕ Nuevo</button>
    <button type="button" onclick="guardarMuestra()">💾 Guardar</button>
  <!--   <button type="button" onclick="refrescarMuestras()">🔄 Refrescar</button>-->
    <button type="button" id="btnEliminar" onclick="eliminarMuestra()" disabled>🗑 Eliminar</button>
</div>
<div id="contenedor-formulario-muestras" style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
<!-- Formulario -->
    <form id="form_muestra" method="POST" action="controllers/agregar_muestra.php">
    <input type="hidden" id="tipo_protocolo_hidden" value="<?= htmlspecialchars($tipo_protocolo_actual) ?>">

        <input type="hidden" name="id_muestra" id="id_muestra">
        <input type="hidden" name="id_protocolo" value="<?= $id_protocolo ?>">

        <label>Tipo de muestra:</label><input type="text" name="tipo_muestra" id="tipo_muestra" required>
        <label>Lote:</label><input type="text" name="lote" id="lote" required>
        <label>Cantidad:</label><input type="number" name="cantidad" id="cantidad" required>
        <label>Edad:</label><input type="text" name="edad" id="edad" required>
        <label>Variedad:</label><input type="text" name="variedad" id="variedad">
       

        <div id="campos_vacunas" style="display:none;">
            <h4>Datos Vacuna</h4>
            <label>Tipo:</label><input type="text" name="tipo_vacuna" id="tipo_vacuna">
            <label>Marca:</label><input type="text" name="marca_vacuna" id="marca_vacuna">
            <label>Dosis:</label><input type="text" name="dosis" id="dosis">
            <label>Fecha elaboración:</label><input type="date" name="fecha_elaboracion" id="fecha_elaboracion">
            <label>Fecha vencimiento:</label><input type="date" name="fecha_vencimiento" id="fecha_vencimiento">
        </div>

        <div id="campos_camarones" style="display:none;">
            <h4>Datos Camarones</h4>
            <label><input type="checkbox" name="wssv"> WSSV</label>
            <label><input type="checkbox" name="tsv"> TSV</label>
            <label><input type="checkbox" name="ihhnv"> IHHNV</label>
            <label><input type="checkbox" name="imnv"> IMNV</label>
            <label><input type="checkbox" name="yhv"> YHV</label>
            <label><input type="checkbox" name="mrnv"> MrNV</label>
            <label><input type="checkbox" name="pvnv"> PvNV</label>
            <label><input type="checkbox" name="ahpnd_ems"> AHPND/EMS</label>
            <label><input type="checkbox" name="ehp"> EHP</label>
            <label><input type="checkbox" name="nhpb"> NHPB</label>
            <label><input type="checkbox" name="div1"> DIV 1</label>
        </div>
       <div class="form-group">
            <label>Análisis seleccionados:</label>
            <table id="tabla_analisis_muestra" border="1" width="100%" style="margin-bottom: 10px;">
                <thead>
                    <tr><th>Nombres</th><th>Precio</th><th>Quitar</th></tr>
                </thead>
                <tbody id="cuerpo_analisis_muestra"> 
                </tbody>
            </table>
            <button type="button" onclick="abrirModalAnalisis()">+ Agregar Análisis</button>
        </div>
    </form>
</div>
<!-- Modal de búsqueda de análisis -->
<div id="modal_analisis" class="modal-overlay" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Buscar análisis</h3>
      <button onclick="cerrarModalAnalisis()">✖</button>
    </div>
    <input type="text" id="input_buscar_analisis" oninput="filtrarAnalisis()" placeholder="Buscar análisis..." style="width:100%;padding:6px;">
    <div id="lista_analisis_modal" style="max-height:300px;overflow-y:auto;margin-top:10px;"></div>
  </div>
</div>

<style>
    .modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4); z-index: 1000;
    display: flex; align-items: center; justify-content: center;
    }
    .modal-content {
    background: #fff; padding: 20px; width: 500px; border-radius: 6px; box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    .modal-header {
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;
    }
    .modal-content button { cursor: pointer; }
</style>
<style>
    #contenedor-tabla-muestras {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid #ccc;
        position: relative;
    }

    #tabla_muestras {
        border-collapse: collapse;
        width: 100%;
    }

    #tabla_muestras thead th {
        position: sticky;
        top: 0;
        background-color:rgb(71, 78, 78);
        z-index: 2;
        text-align: left;
        padding: 1px;
    }

    #tabla_muestras thead tr:first-child th {
        top: 0; /* filtros */     
    }

    #tabla_muestras thead tr:first-child  input {
            width: 80%;
            height: 1px;
        }

    #tabla_muestras thead tr:nth-child(2) th {
        top: 35px; /* fila de títulos debajo */
    }

    #tabla_muestras td {
        padding: 6px;
        border-bottom: 1px solid #ccc;
    }
    

    #contenedor-formulario-muestras {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ccc;
        padding: 10px;
        background: #f9f9f9;
    }
    .fila-seleccionada {
        background-color: #c8e6c9; /* Verde claro */
    }
    .barra-herramientas {
        margin-bottom: 10px;
        display: flex;
        gap: 10px;
    }

    .barra-herramientas button {
        background-color: #00695c;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
    }

   /* #tabla_muestras {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    #tabla_muestras th, #tabla_muestras td {
        border: 1px solid #ccc;
        padding: 2px;
        text-align: left;
        height: 1px;
    }

    #tabla_muestras thead tr input {
        width: 80%;
        height: 1px;
    }

    #tabla_muestras tr:hover {
        background-color: #e0f2f1;
        cursor: pointer;
    }*/

    form label {
        display: block;
        margin-top: 5px;
    }

    form input[type="text"], form input[type="number"], form input[type="date"] {
        width: 100%;
        padding: 5px;
        margin-bottom: 5px;
        border-radius: 3px;
        border: 1px solid #ccc;
    }
</style>

<script>
    function abrirModalAnalisis() {
    document.getElementById('modal_analisis').style.display = 'flex';
    document.getElementById('input_buscar_analisis').focus();
    filtrarAnalisis();
    }

    function cerrarModalAnalisis() {
    document.getElementById('modal_analisis').style.display = 'none';
    }

    // Buscar y listar análisis
    function filtrarAnalisis() {
    const q = document.getElementById('input_buscar_analisis').value;
    fetch('controllers/buscar_analisis_ajax.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
        const contenedor = document.getElementById('lista_analisis_modal');
        contenedor.innerHTML = '';
        if (data.length === 0) {
            contenedor.innerHTML = '<p>No se encontraron análisis.</p>';
            return;
        }
        data.forEach(a => {
            const fila = document.createElement('div');
            fila.className = 'analisis-item';
            fila.style.padding = '6px';
            fila.style.borderBottom = '1px solid #ddd';
            fila.style.cursor = 'pointer';
            fila.innerHTML = `<strong>${a.nombre_estudio}</strong> - Q ${parseFloat(a.precio).toFixed(2)}`;
            fila.onclick = () => {
            agregarAnalisisDesdeModal(a.id_analisis, a.nombre_estudio, parseFloat(a.precio));
            cerrarModalAnalisis();
            };
            contenedor.appendChild(fila);
        });
        });
    }

    function agregarAnalisisDesdeModal(id, nombre, precio) {
        if (document.querySelector(`#analisis-item-${id}`)) return;
        const tabla = document.getElementById("tabla_analisis_muestra");
        const fila = document.createElement("tr");
        fila.id = `analisis-item-${id}`;
        fila.innerHTML = `
            <td>${nombre}</td>
            <td>Q ${precio.toFixed(2)}</td>
            <td><button type="button" onclick="this.closest('tr').remove()">❌</button></td>
            <input type="hidden" name="analisis_ids[]" value="${id}">
        `;
        document.getElementById("cuerpo_analisis_muestra").appendChild(fila);
    }
    function cargarAnalisisDeMuestra(idMuestra) {
    fetch("controllers/obtener_analisis_muestra.php?id_muestra=" + idMuestra)
        .then(response => response.json())
        .then(analisis => {
        const tabla = document.getElementById("tabla_analisis_muestra");
        tabla.innerHTML = '<thead><tr><th>Nombres</th><th>Precio</th><th>Quitar</th></tr></thead><tbody id="cuerpo_analisis_muestra"></tbody>';

        analisis.forEach(a => {
            const fila = document.createElement("tr");
            fila.id = `analisis-item-${a.id_analisis}`;
            fila.innerHTML = `
            <td>${a.nombre_estudio}</td>
            <td>Q ${parseFloat(a.precio).toFixed(2)}</td>
            <td><button type="button" onclick="this.closest('tr').remove()">❌</button></td>
            <input type="hidden" name="analisis_ids[]" value="${a.id_analisis}">
            `;
            document.getElementById("cuerpo_analisis_muestra").appendChild(fila);
            
        });
        });
    }
    function eliminarFilaAnalisis(id) {
        const fila = document.getElementById(`analisis-item-${id}`);
        if (fila) {
            fila.remove();
        }
    }
     function eliminarFilasAnalisis() {
        const fila = document.getElementById(`analisis-item-${id}`);
        if (fila) {
            fila.remove();
        }
    }

</script>    
