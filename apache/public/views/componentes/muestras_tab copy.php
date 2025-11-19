<?php
$id_protocolo = $_GET['id'] ?? null;

?>


<!-- Tabla de muestras -->
<h4>Muestras Registradas</h4>
<table id="tabla_muestras">
    <thead>
        <tr>
            <th><input type="text" onkeyup="filtrarTabla(0)" placeholder="Filtrar tipo"></th>
            <th><input type="text" onkeyup="filtrarTabla(1)" placeholder="Lote"></th>
            <th><input type="text" onkeyup="filtrarTabla(2)" placeholder="Cantidad"></th>
            <th><input type="text" onkeyup="filtrarTabla(3)" placeholder="Edad"></th>
            <th><input type="text" onkeyup="filtrarTabla(4)" placeholder="Variedad"></th>
            <th><input type="text" onkeyup="filtrarTabla(5)" placeholder="Prueba"></th>
            <th>Acciones</th>
        </tr>
        <tr>
            <th>Tipo</th>
            <th>Lote</th>
            <th>Cantidad</th>
            <th>Edad</th>
            <th>Variedad</th>
            <th>Prueba</th>
            <th>Editar / Eliminar</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Traer las muestras existentes
        $stmt = $conexion->prepare("SELECT * FROM muestras WHERE id_protocolo = ?");
        $stmt->execute([$id_protocolo]);
        while ($muestra = $stmt->fetch()) {
            echo "<tr onclick='seleccionarMuestra(".json_encode($muestra).")'>";
            echo "<td>".htmlspecialchars($muestra['tipo_muestra'])."</td>";
            echo "<td>".htmlspecialchars($muestra['lote'])."</td>";
            echo "<td>".htmlspecialchars($muestra['cantidad'])."</td>";
            echo "<td>".htmlspecialchars($muestra['edad'])."</td>";
            echo "<td>".htmlspecialchars($muestra['variedad'])."</td>";
            echo "<td>".htmlspecialchars($muestra['prueba_solicitada'])."</td>";
            echo "<td><button type='button' onclick='eliminarMuestra(".$muestra['id_muestra'].")'>🗑</button></td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<hr>

<!-- Barra de herramientas -->
<div class="barra-herramientas">
    <button type="button" onclick="nuevaMuestra()">➕ Nuevo</button>
    <button type="button" onclick="document.getElementById('form_muestra').submit()">💾 Guardar</button>
    <button type="button" onclick="refrescarMuestras()">🔄 Refrescar</button>
</div>

<!-- Formulario -->
<form id="form_muestra" action="controllers/agregar_muestra.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id_muestra" id="id_muestra">
    <input type="hidden" name="id_protocolo" value="<?= $id_protocolo ?>">

    <label>Tipo de muestra:</label><input type="text" name="tipo_muestra" id="tipo_muestra">

    <label>Lote:</label><input type="text" name="lote" id="lote">

    <label>Cantidad:</label><input type="number" name="cantidad" id="cantidad">

    <label>Edad:</label><input type="text" name="edad" id="edad">

    <label>Variedad:</label><input type="text" name="variedad" id="variedad">

    <label>Prueba solicitada:</label><input type="text" name="prueba_solicitada" id="prueba_solicitada">

    <!-- Campos dinámicos -->
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
        <!-- Campos camarones que ya definimos -->
    </div>
</form>

<script>
    function seleccionarMuestra(muestra) {
        document.getElementById('id_muestra').value = muestra.id_muestra;
        document.getElementById('tipo_muestra').value = muestra.tipo_muestra;
        document.getElementById('lote').value = muestra.lote;
        document.getElementById('cantidad').value = muestra.cantidad;
        document.getElementById('edad').value = muestra.edad;
        document.getElementById('variedad').value = muestra.variedad;
        document.getElementById('prueba_solicitada').value = muestra.prueba_solicitada;

        // Mostrar campos dinámicos según protocolo
      //  mostrarCamposDinamicos();
    }

    function nuevaMuestra() {
        document.getElementById('form_muestra').reset();
        document.getElementById('id_muestra').value = '';
      //  mostrarCamposDinamicos();
    }

    function guardarMuestra() {
    document.getElementById('form_muestra').submit();
    }

    function refrescarMuestras() {
        location.reload();
    }

    function filtrarTabla(columna) {
        const input = document.querySelectorAll("#tabla_muestras thead tr:first-child input")[columna];
        const filtro = input.value.toUpperCase();
        const filas = document.querySelectorAll("#tabla_muestras tbody tr");

        filas.forEach(fila => {
            const celda = fila.getElementsByTagName("td")[columna];
            if (celda && celda.textContent.toUpperCase().includes(filtro)) {
                fila.style.display = "";
            } else {
                fila.style.display = "none";
            }
        });
    }

    function mostrarCamposDinamicos() {
        const tipoProtocolo = '<?= $tipo_protocolo_actual ?>'; // Lo debes pasar desde protocolo
        document.getElementById('campos_vacunas').style.display = tipoProtocolo.includes('Vacuna') ? 'block' : 'none';
        document.getElementById('campos_camarones').style.display = tipoProtocolo.includes('Camarones') ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', mostrarCamposDinamicos);
</script>
<style>
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

    #tabla_muestras {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    #tabla_muestras th, #tabla_muestras td {
        border: 1px solid #ccc;
        padding: 5px;
        text-align: left;
    }

    #tabla_muestras thead tr:first-child input {
        width: 95%;
    }

    #tabla_muestras tr:hover {
        background-color: #e0f2f1;
        cursor: pointer;
    }

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
