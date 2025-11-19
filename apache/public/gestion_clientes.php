<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();



if (!verificarPermiso(5, 'consultar')) {
    header("Location: dashboard.php"); // o muestra mensaje
    exit();
}
/*
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== '1') {
    header("Location: index.php");
    exit();
}*/

$id_cliente = $_GET['id'] ?? null;
$cliente = null;

if ($id_cliente) {
    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
    $stmt->execute([':id' => $id_cliente]);
    $cliente = $stmt->fetch();
}

include "views/header.php";
include "views/menu.php";
?>

<div  id="main-content" class="main-content" style="display: flex; height: 90vh;">
    <!-- Panel izquierdo: Lista -->
    <div  id="panel-lista" style="width: 30%; border-right: 1px solid #ccc; padding: 10px;">
        <h3>Clientes</h3>
        <input type="text" id="buscador" placeholder="Buscar cliente..." style="width: 100%; margin-bottom: 10px;">
        <ul id="lista-clientes" style="list-style: none; padding: 0; height: 70vh; overflow-y: auto; border: 1px solid #ccc;"></ul>
    </div>

    <!-- Panel derecho: Formulario -->
    <div  id="panel-detalle" style="width: 70%; padding: 20px;">
        <h3><?= $cliente ? "Editar Cliente" : "Nuevo Cliente" ?></h3>
        <form action="controllers/cliente_guardar.php" method="POST">
            <?php if ($cliente): ?>
                <input type="hidden" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">
            <?php endif; ?>

            <label>Nombre:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>" required>
 

            <label>Teléfono:</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">

            <label>Correo:</label>
            <input type="email" name="correo" value="<?= htmlspecialchars($cliente['correo'] ?? '') ?>">

            <label>Dirección:</label>
            <input type="text" name="direccion" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">

            <div style="margin-top: 10px;">
                <button type="submit"><?= $cliente ? "Actualizar" : "Guardar" ?></button>
                <?php if ($cliente): ?>
                    <a href="gestion_fincas.php?id_cliente=<?= $cliente['id_cliente'] ?>" style="margin-left: 10px;">🔍 Ver Fincas</a>
                <?php endif; ?>
            </div>
        </form>
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
                lista.innerHTML = '<li>🔍 No se encontraron resultados.</li>';
            } else {
                const activo = new URLSearchParams(window.location.search).get("id");
                clientes.forEach(c => {
                    const li = document.createElement('li');
                    li.className = "cliente-item" + (c.id_cliente === activo ? " activo" : "");
                    li.innerHTML = `<a href="gestion_clientes.php?id=${c.id_cliente}&search=${encodeURIComponent(terminoBusqueda)}">
                                        <strong>${c.id_cliente}</strong> - ${c.nombre}
                                    </a>`;
                    lista.appendChild(li);
                });
            }
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

<?php include "views/footer.php"; ?>
