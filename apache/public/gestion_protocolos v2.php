<?php
session_start();
require_once "config/helpers.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$rol = $_SESSION['usuario']['rol_nombre'];
$id_cliente_sesion = $_SESSION['usuario']['id_cliente'] ?? null;

$id_protocolo = $_GET['id'] ?? null;
$protocolo = null;
$muestras = [];

if ($id_protocolo) {
    $stmt = $conexion->prepare("SELECT p.*, c.nombre nombre_cliente,  f.nombre_finca FROM protocolos p inner join clientes c on p.id_cliente = c.id_cliente left join fincas f on p.id_finca = f.id_finca  WHERE id_protocolo = :id");
    $stmt->execute([':id' => $id_protocolo]);
    $protocolo = $stmt->fetch();

    $stmt = $conexion->prepare("SELECT * FROM muestras WHERE id_protocolo = :id");
    $stmt->execute([':id' => $id_protocolo]);
    $muestras = $stmt->fetchAll();
}

$clientes = [];
$fincas = [];
$tipos = [];

if ($rol !== 'cliente') {
    $clientes = $conexion->query("SELECT id_cliente, nombre FROM clientes ORDER BY nombre")->fetchAll();
} else {
    $stmt = $conexion->prepare("SELECT * FROM fincas WHERE id_cliente = :id_cliente");
    $stmt->execute([':id_cliente' => $id_cliente_sesion]);
    $fincas = $stmt->fetchAll();
}

$tipos  = $conexion->query("SELECT id_tipo_protocolo, nombre_tipo FROM tipos_protocolo ORDER BY 1")->fetchAll();


include "views/header.php";
include "views/menu.php";
?>

<style>
    #main-content {
        display: flex;
        height: calc(100vh - 60px);
    }

    #panel-lista {
        width: 250px;
        background-color: #f5f5f5;
        overflow-y: auto;
        border-right: 1px solid #ccc;
        padding: 10px;
    }

    #panel-detalle {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    #barra-herramientas {
        background-color: #004d40;
        color: white;
        padding: 10px;
        position: sticky;
        top: 0;
        z-index: 10;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    #barra-herramientas button,
    #barra-herramientas a {
        padding: 8px 15px;
        background-color: #00695c;
        color: white;
        text-decoration: none;
        border: none;
        cursor: pointer;
        min-width: 100px;
        text-align: center;
    }

    .tabs {
        position: sticky;
        top: 50px;
        background-color: #eee;
        padding: 8px;
        z-index: 9;
        border-bottom: 1px solid #ccc;
        display: flex;
        gap: 10px;
    }

    #contenido-pestanas {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        min-height: 0;
    }

    #panel-lista ul {
        list-style: none;
        padding: 0;
    }

    #panel-lista li {
        padding: 5px 10px;
        border-bottom: 1px solid #ccc;
        cursor: pointer;
    }

    #panel-lista li:hover {
        background-color: #ddd;
    }
</style>

<div id="main-content">

    <!-- PANEL IZQUIERDO -->
    <div id="panel-lista">
        <h3>Protocolos</h3>
        <input type="text" id="buscador" placeholder="Buscar protocolo..." style="width: 100%; margin-bottom: 10px;">
        <ul id="lista-protocolos" style="list-style: none; padding: 0; height: 70vh; overflow-y: auto; border: 1px solid #ccc;"></ul>
    </div>

    <!-- PANEL DERECHO -->
    <div id="panel-detalle">

        <!-- Barra herramientas -->
        <div id="barra-herramientas">
             <div style="display: inline-block;">
                <button type="button" onclick="document.getElementById('form_protocolo').submit()" 
                        style="padding: 8px 15px; background-color: #00695c; color: white; border: none; min-width: 120px;">
                    💾 Guardar
                </button>
            </div>

            <?php if ($protocolo): ?>
                <a href="gestion_protocolos.php?id=<?= $protocolo['id_protocolo'] ?>" 
                style="padding: 8px 15px; background-color: #00796b; color: white; text-decoration: none; min-width: 120px; text-align: center;">
                    🔄 Refrescar
                </a>
            <?php endif; ?>

            <a href="gestion_protocolos.php" 
            style="padding: 8px 15px; background-color: #004d40; color: white; text-decoration: none; min-width: 120px; text-align: center;">
                ➕ Nuevo
            </a>
        </div>

        <!-- Tabs -->
        <div class="tabs">
             <ul style="list-style:none; display:flex; gap:15px; padding:0; border-bottom:1px solid #ccc;">
                <li><a href="#" onclick="mostrarTab('datos')" class="tablink activo">📝 Datos del Protocolo</a></li>
                <?php if ($id_protocolo): ?>
                    <li><a href="#" onclick="mostrarTab('muestras')" class="tablink">🧪 Muestras</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Contenido de pestañas -->
        <div id="contenido-pestanas">
            <div id="tab_datos">
                 <form id="form_protocolo" action="controllers/protocolo_guardar.php" method="POST" enctype="multipart/form-data">
                
                    <?php if ($protocolo): ?>
                        <label>Id interno:</label>
                        <input type="text" name="id_protocolo" value="<?= $protocolo['id_protocolo'] ?>" readonly>
                    <?php endif; ?>
            <!-- Cliente -->
                    <?php if ($rol !== 'cliente'): ?>
                        <label>Cliente:</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="hidden" name="id_cliente" id="id_cliente" value="<?= $protocolo['id_cliente'] ?? '' ?>">
                            <input type="text" id="nombre_cliente" value="<?= $protocolo['nombre_cliente'] ?? '' ?>" readonly 
                                style="flex: 1; padding: 5px; min-width: 200px;">
                            <?php if (!$protocolo): ?>
                                <button type="button" onclick="abrirModalCliente()" 
                                        style="padding: 5px 10px; white-space: nowrap;">Buscar</button>
                            <?php else: ?>
                                <button type="button" disabled 
                                        style="padding: 5px 10px; white-space: nowrap; background-color: #ccc;">Buscar</button>
                            <?php endif; ?>
                        </div>
                    
                    <?php else: ?>
                        <input type="hidden" name="id_cliente"  id="id_cliente" value="<?= $id_cliente_sesion ?>">
                    <?php endif; ?>

            <!-- Finca -->
                    <label>Unidad Productiva:</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="id_finca" id="id_finca" value="<?= $protocolo['id_finca'] ?? '' ?>">
                        <input type="text" id="nombre_finca" value="<?= $protocolo['nombre_finca'] ?? '' ?>" readonly 
                            style="flex: 1; padding: 5px; min-width: 200px;">
                        <button type="button" onclick="abrirModalFinca()">Buscar</button>
                    </div>

            <!-- Tipo de Protocolo -->
                    <label>Tipo de Protocolo:</label>
                    <select name="id_tipo_protocolo" id="select_tipo_protocolo" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?= $tipo['id_tipo_protocolo'] ?>" <?= (isset($protocolo['id_tipo_protocolo']) && $protocolo['id_tipo_protocolo'] == $tipo['id_tipo_protocolo']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipo['nombre_tipo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Fecha:</label>
                    <input type="date" name="fecha" value="<?= $protocolo['fecha'] ?? '' ?>" required>

                    <label>Tipo de Material Remitido:</label>
                    <input type="text" name="tipo_material" value="<?= htmlspecialchars($protocolo['tipo_material'] ?? '') ?>">

                    <label>M.V. que remite:</label>
                    <input type="text" name="mv_remite" value="<?= htmlspecialchars($protocolo['mv_remite'] ?? '') ?>">

                    <label>Correo Electrónico:</label>
                    <input type="email" name="correo" value="<?= htmlspecialchars($protocolo['correo'] ?? '') ?>">

                    <label>Departamento:</label>
                    <input type="text" name="departamento" value="<?= htmlspecialchars($protocolo['departamento'] ?? '') ?>">

                    <label>Municipio:</label>
                    <input type="text" name="municipio" value="<?= htmlspecialchars($protocolo['municipio'] ?? '') ?>">

                    <label>Coordenadas Vertical:</label>
                    <input type="text" name="coordenada_vertical" value="<?= htmlspecialchars($protocolo['coordenada_vertical'] ?? '') ?>">

                    <label>Coordenadas Horizontal:</label>
                    <input type="text" name="coordenada_horizontal" value="<?= htmlspecialchars($protocolo['coordenada_horizontal'] ?? '') ?>">

                    <label>Procedencia:</label>
                    <input type="text" name="procedencia" value="<?= htmlspecialchars($protocolo['procedencia'] ?? '') ?>">

                    <label>Prueba Solicitada (Titulación/Evaluación):</label>
                    <input type="text" name="prueba_solicitada" value="<?= htmlspecialchars($protocolo['prueba_solicitada'] ?? '') ?>">

                    <label>Material Solicitado:</label>
                    <input type="text" name="material_solicitado" value="<?= htmlspecialchars($protocolo['material_solicitado'] ?? '') ?>">

                    <label>Observaciones:</label>
                    <textarea name="observaciones"><?= htmlspecialchars($protocolo['observaciones'] ?? '') ?></textarea>

                    <label>Estado de la Muestra:</label>
                    <select name="estado_muestra">
                        <option value="buen" <?= ($protocolo['estado_muestra'] ?? '') === 'buen' ? 'selected' : '' ?>>Buen estado</option>
                        <option value="mal" <?= ($protocolo['estado_muestra'] ?? '') === 'mal' ? 'selected' : '' ?>>Mal estado</option>
                    </select>

                    <label>Entrega de Resultados:</label>
                    <div>
                    <label>Personal</label>
                        <input type="checkbox" name="entrega_personal" value="1" <?= (!empty($protocolo['entrega_personal'])) ? 'checked' : '' ?>> 
                    <label>Correo Electrónico</label>
                        <input type="checkbox" name="entrega_correo" value="1" <?= (!empty($protocolo['entrega_correo'])) ? 'checked' : '' ?>> 
                    </div>

                    <!-- Firma -->
                    <label>Firma del Cliente:</label><br>
                    <canvas id="canvas" width="400" height="150" style="border:1px solid #000;"></canvas><br>
                    <button type="button" onclick="limpiarFirma()">🧹 Limpiar Firma</button>
                    <input type="hidden" name="firma_imagen" id="firma_imagen">
                    <!-- 
                    <div style="margin-top:15px;">
                        <button type="submit"><?= $protocolo ? "Actualizar" : "Guardar" ?></button>
                        <a href="gestion_protocolos.php" style="margin-left: 10px;">Cancelar</a>
                    </div>Firma -->
                
            </form>
            </div>

            <div id="tab_muestras" style="display: none;">
                <?php include "views/componentes/muestras_tab.php"; ?>
            </div>
        </div>

    </div>
</div>

<script>
    function mostrarTab(tabId) {
        document.getElementById('tab_datos').style.display = 'none';
        document.getElementById('tab_muestras').style.display = 'none';
        document.getElementById(tabId).style.display = 'block';
    }

    // Mostrar Datos por defecto
    mostrarTab('tab_datos');
    const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
let dibujando = false;

// Ajustar tamaño del canvas si es necesario
function resizeCanvas() {
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
}
resizeCanvas(); // Al cargar

// -----------------------
// FUNCIONES DE POSICIÓN
// -----------------------
function getCanvasPos(evt) {
    const rect = canvas.getBoundingClientRect();
    let x, y;

    if (evt.touches && evt.touches[0]) {
        x = evt.touches[0].clientX - rect.left;
        y = evt.touches[0].clientY - rect.top;
    } else {
        x = evt.clientX - rect.left;
        y = evt.clientY - rect.top;
    }

    return { x, y };
}

// -----------------------
// EVENTOS MOUSE
// -----------------------
canvas.addEventListener('mousedown', e => {
    dibujando = true;
    const pos = getCanvasPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
});

canvas.addEventListener('mousemove', e => {
    if (!dibujando) return;
    const pos = getCanvasPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
});

canvas.addEventListener('mouseup', () => {
    dibujando = false;
    guardarFirma();
});

// -----------------------
// EVENTOS TOUCH
// -----------------------
canvas.addEventListener('touchstart', e => {
    e.preventDefault();
    dibujando = true;
    const pos = getCanvasPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
});

canvas.addEventListener('touchmove', e => {
    e.preventDefault();
    if (!dibujando) return;
    const pos = getCanvasPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
});

canvas.addEventListener('touchend', () => {
    dibujando = false;
    guardarFirma();
});

// -----------------------
// FUNCIONES EXTRA
// -----------------------
function guardarFirma() {
    const dataURL = canvas.toDataURL();
    document.getElementById('firma_imagen').value = dataURL;
}

function limpiarFirma() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    guardarFirma();
}
// Tabs
function mostrarTabss(id) {
    document.querySelectorAll('.tab-seccion').forEach(tab => tab.style.display = 'none');
    document.getElementById(`tab-${id}`).style.display = 'block';
    document.querySelectorAll('.tablink').forEach(t => t.classList.remove('activo'));
    document.querySelector(`.tablink[href="#"][onclick*='${id}']`).classList.add('activo');
}

// Scroll infinito de protocolos
let pagina = 1;
let terminoBusqueda = '';
let cargando = false;

function cargarProtocolos(reset = false) {
    if (cargando) return;
    cargando = true;

    const lista = document.getElementById('lista-protocolos');
    fetch(`controllers/buscar_protocolos.php?busqueda=${encodeURIComponent(terminoBusqueda)}&pagina=${pagina}`)
        .then(res => res.json())
        .then(protocolos => {
            if (reset) lista.innerHTML = '';
            if (protocolos.length === 0 && pagina === 1) {
                lista.innerHTML = '<li>Sin resultados.</li>';
            } else {
                const actual = new URLSearchParams(window.location.search).get("id");
                protocolos.forEach(p => {
                    const li = document.createElement('li');
                    li.className = "cliente-item" + (p.id_protocolo == actual ? " activo" : "");
                    li.innerHTML = `<a href="gestion_protocolos.php?id=${p.id_protocolo}">${p.id_protocolo} -${p.id_cliente}  - ${p.fecha}</a>`;
                    lista.appendChild(li);
                });
            }
            cargando = false;
        });
}

document.getElementById('buscador').addEventListener('input', () => {
    terminoBusqueda = document.getElementById('buscador').value;
    pagina = 1;
    cargarProtocolos(true);
});

document.getElementById('lista-protocolos').addEventListener('scroll', () => {
    const lista = document.getElementById('lista-protocolos');
    if (lista.scrollTop + lista.clientHeight >= lista.scrollHeight - 10) {
        pagina++;
        cargarProtocolos();
    }
});

// Cargar fincas por cliente
function cargarFincas(idCliente) {
    fetch(`controllers/obtener_fincas.php?id_cliente=${idCliente}`)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('finca_select');
            select.innerHTML = '<option value="">-- Seleccione una finca --</option>';
            data.forEach(f => {
                const opt = document.createElement('option');
                opt.value = f.id_finca;
                opt.text = f.nombre_finca;
                select.appendChild(opt);
            });
        });
}



cargarProtocolos();


function abrirModalCliente() {
    document.getElementById('modalCliente').style.display = 'block';
    buscarClientes();
}

function cerrarModalCliente() {
    document.getElementById('modalCliente').style.display = 'none';
}

function buscarClientes() {
    const termino = document.getElementById('busquedaCliente').value;
    fetch(`controllers/buscar_clientes_modal.php?busqueda=${encodeURIComponent(termino)}`)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('tablaClientes').querySelector('tbody');
            tbody.innerHTML = '';
            data.forEach(c => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${c.id_cliente}</td>
                                <td>${c.nombre}</td>
                                <td><button type="button" onclick="seleccionarCliente(${c.id_cliente}, '${c.nombre}')">Seleccionar</button></td>`;
                tbody.appendChild(tr);
            });
        });
}

function seleccionarCliente(id, nombre) {
    document.getElementById('id_cliente').value = id;
    document.getElementById('nombre_cliente').value = nombre;
    cerrarModalCliente();
    // Aquí puedes limpiar finca o cargar nuevas fincas si quieres automáticamente
}


function abrirModalFinca() {
    const idCliente = document.getElementById('id_cliente').value;     
    if (!idCliente) {
        alert("Primero debe seleccionar un cliente.");
        return;
    }
    document.getElementById('modalFinca').style.display = 'block';
    buscarFincas();
}

function cerrarModalFinca() {
    document.getElementById('modalFinca').style.display = 'none';
}

function buscarFincas() {
    const termino = document.getElementById('busquedaFinca').value;
    const idCliente = document.getElementById('id_cliente').value;

    fetch(`controllers/buscar_fincas_modal.php?busqueda=${encodeURIComponent(termino)}&id_cliente=${idCliente}`)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('tablaFincas').querySelector('tbody');
            tbody.innerHTML = '';
            data.forEach(f => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${f.id_finca}</td>
                                <td>${f.nombre_finca}</td>
                                <td><button type="button" onclick="seleccionarFinca(${f.id_finca}, '${f.nombre_finca}')">Seleccionar</button></td>`;
                tbody.appendChild(tr);
            });
        });
}

function seleccionarFinca(id, nombre) {
    document.getElementById('id_finca').value = id;
    document.getElementById('nombre_finca').value = nombre;
    cerrarModalFinca();
}

</script>

<?php include "modal_buscar_cliente.php"; ?>
<?php include "modal_buscar_finca.php"; ?>
<?php include "views/footer.php"; ?>