<?php
require_once "config/session.php";
require_once "config/helpers.php";
?>
<!-- jQuery (necesario para Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<div id="main-content" class="main-content">

<?php 

if (!verificarPermiso(10, 'consultar')) {
    header("Location: dashboard.php"); // o muestra mensaje
    exit();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$rol = $_SESSION['usuario']['rol_nombre'];
$id_cliente_sesion = $_SESSION['usuario']['id_cliente'] ?? null;

$clientes = [];
$fincas = [];

if ($rol === 'cliente') {
    // Solo ver sus propias fincas
    $stmt = $conexion->prepare("SELECT * FROM fincas WHERE id_cliente = :id_cliente");
    $stmt->execute([':id_cliente' => $id_cliente_sesion]);
    $fincas = $stmt->fetchAll();
} else {
    // Usuarios normales: cargan todos los clientes
    $clientes = $conexion->query("SELECT id_cliente, nombre FROM clientes ORDER BY nombre")->fetchAll();
}


include "views/header.php";
include "views/menu.php";

$stmt = $conexion->prepare("SELECT id_tipo_protocolo, nombre_tipo, prefijo FROM tipos_protocolo WHERE activo = TRUE ORDER BY nombre_tipo");
$stmt->execute();
$tipos_protocolo = $stmt->fetchAll();
?>

<h2>Registro de Protocolos</h2>

<div class="protocolos-container">
    <form action="controllers/protocolos.php" method="POST" enctype="multipart/form-data">

		<?php if ($rol !== 'cliente'): ?>
            <!-- Selección de cliente -->
            <label>Cliente:</label>
            
            <select name="id_cliente" id="id_cliente" required onchange="cargarFincas(this.value)">
                <option value="">-- Seleccione un cliente --</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?= $cliente['id_cliente'] ?>"><?= htmlspecialchars($cliente['nombre']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Fincas se llenarán dinámicamente -->
            <label>Finca:</label>
            <select name="id_finca" id="finca_select" required>
                <option value="">-- Seleccione una finca --</option>
            </select>
        <?php else: ?>
            <!-- Para clientes, finca ya cargada -->
            <input type="hidden" name="id_cliente" value="<?= $id_cliente_sesion ?>">
            <label>Finca:</label>
            <select name="id_finca" required>
                <option value="">-- Seleccione una finca --</option>
                <?php foreach ($fincas as $finca): ?>
                    <option value="<?= $finca['id_finca'] ?>"><?= htmlspecialchars($finca['nombre_finca']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
		
        <!-- Tipo de Protocolo -->
        <label>Tipo de Protocolo:</label>
		<select name="id_tipo_protocolo" required>
		<option value="">-- Seleccione un tipo --</option>
			<?php foreach ($tipos_protocolo as $tipo): ?>
        <option value="<?= $tipo['id_tipo_protocolo'] ?>">
            <?= htmlspecialchars($tipo['nombre_tipo']) ?> (<?= $tipo['prefijo'] ?>)
        </option>
			<?php endforeach; ?>
		</select>

        <!-- Campos Generales -->
        <label>Fecha:</label>
        <input type="date" name="fecha" required>
  
        <label>Teléfono:</label>
        <input type="text" name="telefono">

        <label>Correo Electrónico:</label>
        <input type="email" name="correo">

        <label>Dirección:</label>
        <input type="text" name="direccion">

        <label>Protocolo No.:</label>
        <input type="text" name="protocolo_no">

        <!-- Campos Específicos -->
        <label>Granja:</label>
        <input type="text" name="granja">

        <label>Unidad Productiva:</label>
        <input type="text" name="unidad_productiva">

        <label>Edad de los Animales:</label>
        <input type="text" name="edad">

        <label>Número de Aves en el Lote:</label>
        <input type="number" name="aves_lote">

        <label>Tipo de Material Remitido:</label>
        <input type="text" name="tipo_material">

        <label>Especificaciones:</label>
        <textarea name="especificaciones"></textarea>

        <label>Examen Solicitado:</label>
        <input type="text" name="examen_solicitado">

        <label>Observaciones:</label>
        <textarea name="observaciones"></textarea>

        <!-- Estado de la Muestra -->
        <label>Estado de la Muestra:</label>
        <select name="estado_muestra">
            <option value="buen">Buen estado</option>
            <option value="mal">Mal estado</option>
        </select>

        <!-- Entrega de Resultados -->
        <label>Entrega de Resultados:</label>
        <div>
            <input type="checkbox" name="entrega_personal" value="1"> Personal
            <input type="checkbox" name="entrega_correo" value="1"> Correo Electrónico
        </div>
        <label>Firma del Cliente:</label>
<div class="firma-container">
    <canvas id="canvas"></canvas>
    <div class="firma-buttons">
        <button type="button" onclick="clearCanvas()">🧹 Limpiar</button>
    </div>
    <input type="hidden" name="firma_imagen" id="firma_imagen">
</div>


        <!-- Firma Dibujada 
        <label>Firma del Cliente:</label>
        <canvas id="canvas" width="400" height="150" style="border:1px solid #000;"></canvas>
        <button type="button" onclick="clearCanvas()">Limpiar</button>
        <input type="hidden" name="firma_imagen" id="firma_imagen">-->

        <label>Firma de Quien Recibe:</label>
        <input type="text" name="firma_recibe">

        <button type="submit" name="registrar_protocolo">Registrar Protocolo</button>
    </form>
</div>

<script>
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
let drawing = false;

// Ajustes para el dibujo
ctx.lineWidth = 2;
ctx.lineCap = 'round';
ctx.strokeStyle = '#000';

// Eventos para mouse
canvas.addEventListener('mousedown', startDraw);
canvas.addEventListener('mouseup', endDraw);
canvas.addEventListener('mousemove', draw);

// Eventos para pantallas táctiles
canvas.addEventListener('touchstart', startDrawTouch, false);
canvas.addEventListener('touchend', endDrawTouch, false);
canvas.addEventListener('touchmove', drawTouch, false);

function startDraw(e) {
    drawing = true;
    ctx.beginPath();
    ctx.moveTo(e.offsetX, e.offsetY);
}

function draw(e) {
    if (!drawing) return;
    ctx.lineTo(e.offsetX, e.offsetY);
    ctx.stroke();
}

function endDraw() {
    drawing = false;
    saveSignature();
}

function startDrawTouch(e) {
    e.preventDefault();
    drawing = true;
    const touch = e.touches[0];
    const rect = canvas.getBoundingClientRect();
    ctx.beginPath();
    ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
}

function drawTouch(e) {
    e.preventDefault();
    if (!drawing) return;
    const touch = e.touches[0];
    const rect = canvas.getBoundingClientRect();
    ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
    ctx.stroke();
}

function endDrawTouch(e) {
    drawing = false;
    saveSignature();
}

function saveSignature() {
    const dataURL = canvas.toDataURL();
    document.getElementById('firma_imagen').value = dataURL;
}

function clearCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    saveSignature();
}

function cargarFincas(idCliente) {
    fetch('controllers/obtener_fincas.php?id_cliente=' + idCliente)
        .then(response => response.json())
        .then(data => {
            const fincaSelect = document.getElementById('finca_select');
            fincaSelect.innerHTML = '<option value="">-- Seleccione una finca --</option>';
            data.forEach(finca => {
                const option = document.createElement('option');
                option.value = finca.id_finca;
                option.text = finca.nombre_finca;
                fincaSelect.appendChild(option);
            });
        });
} 
$(document).ready(function() {
    $('#id_cliente').select2({
        placeholder: "Seleccione un cliente...",
        width: '100%'
    });
});
</script>
<?php include "views/footer.php"; ?>
</div>