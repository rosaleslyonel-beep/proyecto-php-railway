<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$rol = strtolower(trim($_SESSION['usuario']['rol_nombre'] ?? ''));
$id_cliente_sesion = $_SESSION['usuario']['id_cliente'] ?? null;
$esCliente = ($rol === 'cliente');

$id_cliente = $_GET['id_cliente'] ?? null;
$id_finca = $_GET['id'] ?? null;
$search = $_GET['search'] ?? '';

/*
|--------------------------------------------------------------------------
| Auto-redirect inteligente para rol cliente
|--------------------------------------------------------------------------
*/
if ($esCliente) {
    if (empty($id_cliente_sesion)) {
        die("No hay un cliente asociado a la sesión.");
    }

    if (empty($id_cliente) || (int)$id_cliente !== (int)$id_cliente_sesion) {
        $url = "gestion_fincas.php?id_cliente=" . (int)$id_cliente_sesion;
        if (!empty($id_finca) && is_numeric($id_finca)) {
            $url .= "&id=" . (int)$id_finca;
        }
        if ($search !== '') {
            $url .= "&search=" . urlencode($search);
        }
        header("Location: $url");
        exit();
    }
}

if (!$id_cliente) {
    echo "<p>⚠️ No se ha especificado cliente.</p>";
    exit();
}

$nombre_cliente = '';
$stmtCliente = $conexion->prepare("SELECT nombre FROM clientes WHERE id_cliente = :id");
$stmtCliente->execute([':id' => $id_cliente]);
$nombre_cliente = $stmtCliente->fetchColumn();

if (!$nombre_cliente) {
    echo "<p>⚠️ El cliente indicado no existe o no está disponible.</p>";
    exit();
}

$finca = null;
if ($id_finca && is_numeric($id_finca)) {
    $stmt = $conexion->prepare("SELECT * FROM fincas WHERE id_finca = :id AND id_cliente = :id_cliente");
    $stmt->execute([':id' => $id_finca, ':id_cliente' => $id_cliente]);
    $finca = $stmt->fetch(PDO::FETCH_ASSOC);
}

include "views/header.php";
include "views/menu.php";
?>

<div id="main-content" class="main-content">
    <div id="panel-lista">
        <div class="panel-titulo">Fincas</div>
        <input type="text" id="buscador" placeholder="Buscar finca...">
        <ul id="lista-fincas"></ul>
    </div>

    <div id="panel-detalle">
        <div id="barra-herramientas">
            <a href="gestion_clientes.php?id=<?= (int)$id_cliente ?>" class="btn-barra">← Volver al cliente</a>
            <a href="gestion_fincas.php?id_cliente=<?= (int)$id_cliente ?>" class="btn-barra">Nueva</a>
            <button type="submit" form="form-finca" class="btn-barra">Guardar</button>
            <?php if ($finca): ?>
                <a href="gestion_fincas.php?id_cliente=<?= (int)$id_cliente ?>" class="btn-barra">Cancelar</a>
            <?php endif; ?>
        </div>

        <div id="contenido-detalle">
            <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'guardado'): ?>
                <div class="mensaje-exito">Finca guardada correctamente.</div>
            <?php endif; ?>

            <?php if (!empty($_GET['error'])): ?>
                <div class="mensaje-error"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <h2>Fincas de: <?= htmlspecialchars($nombre_cliente) ?></h2>
            <h3><?= $finca ? "Editar Finca" : "Nueva Finca" ?></h3>

            <form id="form-finca" action="controllers/finca_guardar.php" method="POST" class="formulario-detalle">
                <?php if ($finca): ?>
                    <input type="hidden" name="id_finca" value="<?= (int)$finca['id_finca'] ?>">
                <?php endif; ?>

                <input type="hidden" name="id_cliente" value="<?= (int)$id_cliente ?>">

                <div class="campo">
                    <label for="nombre_finca">Nombre de la Finca:</label>
                    <input type="text" id="nombre_finca" name="nombre_finca" value="<?= htmlspecialchars($finca['nombre_finca'] ?? '') ?>" required>
                </div>

                <div class="campo">
                    <label for="ubicacion">Ubicación:</label>
                    <input type="text" id="ubicacion" name="ubicacion" value="<?= htmlspecialchars($finca['ubicacion'] ?? '') ?>" required>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let pagina = 1;
let terminoBusqueda = '';
let cargando = false;

function cargarFincas(reset = false) {
    if (cargando) return;
    cargando = true;

    const lista = document.getElementById('lista-fincas');

    fetch(`controllers/buscar_fincas.php?busqueda=${encodeURIComponent(terminoBusqueda)}&pagina=${pagina}&id_cliente=<?= (int)$id_cliente ?>`)
        .then(res => res.json())
        .then(fincas => {
            if (reset) lista.innerHTML = '';

            if (fincas.length === 0 && pagina === 1) {
                lista.innerHTML = '<li class="sin-resultados">🔍 No se encontraron fincas.</li>';
            } else {
                const fincaActiva = new URLSearchParams(window.location.search).get('id');

                fincas.forEach(f => {
                    const li = document.createElement('li');
                    const activoClase = (String(f.id_finca) === String(fincaActiva)) ? 'activo' : '';

                    li.className = `finca-item ${activoClase}`;
                    li.innerHTML = `
                        <a href="gestion_fincas.php?id=${f.id_finca}&id_cliente=<?= (int)$id_cliente ?>&search=${encodeURIComponent(terminoBusqueda)}">
                            <strong>ID: ${f.id_finca}</strong><br>
                            <span>${f.nombre_finca}</span>
                        </a>
                    `;
                    lista.appendChild(li);
                });
            }
        })
        .catch(error => {
            console.error(error);
            if (pagina === 1) {
                lista.innerHTML = '<li class="sin-resultados">Error al cargar fincas.</li>';
            }
        })
        .finally(() => {
            cargando = false;
        });
}

document.getElementById('buscador').addEventListener('input', () => {
    terminoBusqueda = document.getElementById('buscador').value;
    pagina = 1;
    cargarFincas(true);
});

document.getElementById('lista-fincas').addEventListener('scroll', () => {
    const lista = document.getElementById('lista-fincas');
    if (lista.scrollTop + lista.clientHeight >= lista.scrollHeight - 10) {
        pagina++;
        cargarFincas();
    }
});

const params = new URLSearchParams(window.location.search);
if (params.has('search')) {
    terminoBusqueda = params.get('search');
    document.getElementById('buscador').value = terminoBusqueda;
}

cargarFincas();
</script>

<style>
#main-content {
    display: flex;
    height: calc(100vh - 60px);
}

#panel-lista {
    width: 30%;
    min-width: 280px;
    max-width: 380px;
    background: #f5f5f5;
    border-right: 1px solid #ccc;
    padding: 10px;
    display: flex;
    flex-direction: column;
}

.panel-titulo {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 10px;
}

#buscador {
    width: 100%;
    margin-bottom: 10px;
    padding: 10px;
    box-sizing: border-box;
}

#lista-fincas {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
    overflow-y: auto;
    border: 1px solid #ccc;
    background: #fff;
}

#lista-fincas li {
    border-bottom: 1px solid #eee;
}

#lista-fincas li a {
    display: block;
    padding: 10px;
    color: #222;
    text-decoration: none;
}

#lista-fincas li:hover {
    background: #e9ecef;
}

#lista-fincas li.activo {
    background: #d7f0ea;
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
    flex-wrap: wrap;
}

.btn-barra {
    padding: 8px 15px;
    background-color: #00695c;
    color: white;
    text-decoration: none;
    border: none;
    cursor: pointer;
    min-width: 110px;
    text-align: center;
    border-radius: 4px;
}

.btn-barra:hover {
    background-color: #00796b;
}

#contenido-detalle {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.formulario-detalle {
    max-width: 700px;
}

.campo {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.campo label {
    font-weight: bold;
    margin-bottom: 5px;
}

.campo input {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.mensaje-exito {
    background: #e8f5e9;
    border: 1px solid #81c784;
    color: #2e7d32;
    padding: 10px 12px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.mensaje-error {
    background: #ffebee;
    border: 1px solid #e57373;
    color: #c62828;
    padding: 10px 12px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.sin-resultados {
    padding: 12px;
    color: #666;
}
</style>

<?php include "views/footer.php"; ?>
