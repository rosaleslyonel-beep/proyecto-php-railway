<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();

if (!verificarPermiso(10, 'consultar')) {
    header("Location: dashboard.php");
    exit();
}

$rol = strtolower(trim($_SESSION['usuario']['rol_nombre'] ?? ''));
$id_cliente_sesion = $_SESSION['usuario']['id_cliente'] ?? null;

$esCliente = ($rol === 'cliente');

$id_cliente = $_GET['id'] ?? null;
$cliente = null;

/*
|--------------------------------------------------------------------------
| Control de acceso:
| - Si es cliente, solo puede abrir su propio cliente.
| - Si no viene id, se carga automáticamente su cliente asignado.
|--------------------------------------------------------------------------
*/
if ($esCliente) {
    if (!$id_cliente_sesion) {
        die("No se encontró un cliente asociado a la sesión.");
    }

    $id_cliente = $id_cliente_sesion;
}

if ($id_cliente) {
    if ($esCliente) {
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = :id AND id_cliente = :id_sesion");
        $stmt->execute([
            ':id' => $id_cliente,
            ':id_sesion' => $id_cliente_sesion
        ]);
    } else {
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
        $stmt->execute([':id' => $id_cliente]);
    }

    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente && $esCliente) {
        die("No tiene permiso para acceder a este cliente.");
    }
}

include "views/header.php";
include "views/menu.php";
?>

<div id="main-content" class="main-content">
    <!-- Panel izquierdo -->
    <div id="panel-lista">
        <div class="panel-titulo">Clientes</div>
        <input type="text" id="buscador" placeholder="Buscar cliente...">
        <ul id="lista-clientes"></ul>
    </div>

    <!-- Panel derecho -->
    <div id="panel-detalle">
        <div id="barra-herramientas">
            <?php if (!$esCliente): ?>
                <a href="gestion_clientes.php" class="btn-barra">Nuevo</a>
            <?php endif; ?>

            <?php if ($cliente): ?>
                <button type="submit" form="form-cliente" class="btn-barra">Guardar</button>

                <a href="gestion_fincas.php?id_cliente=<?= $cliente['id_cliente'] ?>" class="btn-barra">
                    Unidades productivas
                </a>

                <?php if (!$esCliente): ?>
                    <button type="button" class="btn-barra btn-danger" onclick="eliminarCliente(<?= (int)$cliente['id_cliente'] ?>)">
                        Eliminar
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <?php if (!$esCliente): ?>
                    <button type="submit" form="form-cliente" class="btn-barra">Guardar</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="alerta alerta-exito">
                <?php
                switch ($_GET['msg']) {
                    case 'cliente_guardado':
                        echo "✅ Cliente guardado correctamente.";
                        break;
                    case 'cliente_eliminado':
                        echo "🗑️ Cliente eliminado correctamente.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alerta alerta-error">
                ❌ <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div id="contenido-detalle">
            <h3>
                <?= $cliente ? "Editar Cliente" : "Nuevo Cliente" ?>
            </h3>

            <?php if ($esCliente && !$cliente): ?>
                <div class="mensaje-info">
                    No se encontró información del cliente asociado a la sesión.
                </div>
            <?php else: ?>
                <form id="form-cliente" action="controllers/cliente_guardar.php" method="POST" class="formulario-detalle">
                    <?php if ($cliente): ?>
                        <input type="hidden" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">
                    <?php endif; ?>

                    <div class="campo">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre"
                               value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>" required>
                    </div>

                    <div class="campo">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono"
                               value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">
                    </div>

                    <div class="campo">
                        <label for="correo">Correo:</label>
                        <input type="email" id="correo" name="correo"
                               value="<?= htmlspecialchars($cliente['correo'] ?? '') ?>">
                    </div>

                    <div class="campo">
                        <label for="direccion">Dirección:</label>
                        <input type="text" id="direccion" name="direccion"
                               value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
                    </div>

                    <?php if ($esCliente): ?>
                        <div class="mensaje-info" style="margin-top: 15px;">
                            Puede actualizar su información y administrar sus unidades productivas,
                            pero no crear ni eliminar clientes.
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let pagina = 1;
let terminoBusqueda = '';
let cargando = false;

function cargarClientes(reset = false) {
    if (cargando) return;
    cargando = true;

    const lista = document.getElementById('lista-clientes');

    fetch(`controllers/buscar_clientes.php?busqueda=${encodeURIComponent(terminoBusqueda)}&pagina=${pagina}`)
        .then(res => res.json())
        .then(clientes => {
            if (reset) lista.innerHTML = '';

            if (clientes.length === 0 && pagina === 1) {
                lista.innerHTML = '<li class="sin-resultados">🔍 No se encontraron resultados.</li>';
            } else {
                const activo = new URLSearchParams(window.location.search).get("id");

                clientes.forEach(c => {
                    const li = document.createElement('li');
                    const activoClase = (String(c.id_cliente) === String(activo)) ? 'activo' : '';

                    li.className = `cliente-item ${activoClase}`;
                    li.innerHTML = `
                        <a href="gestion_clientes.php?id=${c.id_cliente}&search=${encodeURIComponent(terminoBusqueda)}">
                            <strong>ID: ${c.id_cliente}</strong><br>
                            <span>${c.nombre}</span>
                        </a>
                    `;
                    lista.appendChild(li);
                });
            }
        })
        .catch(error => {
            console.error(error);
            if (pagina === 1) {
                lista.innerHTML = '<li class="sin-resultados">Error al cargar clientes.</li>';
            }
        })
        .finally(() => {
            cargando = false;
        });
}

document.getElementById('buscador').addEventListener('input', () => {
    terminoBusqueda = document.getElementById('buscador').value;
    pagina = 1;
    cargarClientes(true);
});

document.getElementById('lista-clientes').addEventListener('scroll', () => {
    const lista = document.getElementById('lista-clientes');

    if (lista.scrollTop + lista.clientHeight >= lista.scrollHeight - 10) {
        pagina++;
        cargarClientes();
    }
});

function eliminarCliente(id) {
    if (!confirm('¿Está seguro de eliminar este cliente?')) return;

    window.location.href = `controllers/cliente_eliminar.php?id=${id}`;
}

const params = new URLSearchParams(window.location.search);
if (params.has('search')) {
    terminoBusqueda = params.get('search');
    document.getElementById('buscador').value = terminoBusqueda;
}

cargarClientes();
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

#lista-clientes {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
    overflow-y: auto;
    border: 1px solid #ccc;
    background: #fff;
}

#lista-clientes li {
    border-bottom: 1px solid #eee;
}

#lista-clientes li a {
    display: block;
    padding: 10px;
    color: #222;
    text-decoration: none;
}

#lista-clientes li:hover {
    background: #e9ecef;
}

#lista-clientes li.activo {
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

.btn-danger {
    background-color: #c62828;
}

.btn-danger:hover {
    background-color: #b71c1c;
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

.mensaje-info {
    background: #eef7ff;
    border: 1px solid #b6d4fe;
    color: #084298;
    padding: 12px;
    border-radius: 4px;
}

.sin-resultados {
    padding: 12px;
    color: #666;
}

.alerta {
    padding: 12px 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 14px;
}

.alerta-exito {
    background-color: #e6f4ea;
    border: 1px solid #b7e1cd;
    color: #1e7e34;
}

.alerta-error {
    background-color: #fdecea;
    border: 1px solid #f5c6cb;
    color: #a71d2a;
}
</style>

<?php include "views/footer.php"; ?>