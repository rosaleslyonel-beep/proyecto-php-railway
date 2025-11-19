<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();
if (!verificarPermiso(9, 'consultar')) {
    header("Location: dashboard.php"); // o muestra mensaje
    exit();
}

$id_pantalla = $_GET['id'] ?? null;
$pantalla = null;

if ($id_pantalla) {
    $stmt = $conexion->prepare("SELECT * FROM pantallas WHERE id_pantalla = :id");
    $stmt->execute([':id' => $id_pantalla]);
    $pantalla = $stmt->fetch();
}

include "views/header.php";
include "views/menu.php";
?>

<div  id="main-content">
    <!-- Panel izquierdo -->
    <div  id="panel-lista">
        <h3>Pantallas del Sistema</h3>
        <input type="text" id="buscador" placeholder="Buscar pantalla..." style="width: 100%; margin-bottom: 10px;">
        <ul id="lista-pantallas" style="list-style: none; padding: 0; height: 70vh; overflow-y: auto; border: 1px solid #ccc;"></ul>
    </div>

    <!-- Panel derecho -->
    <div  id="panel-detalle">
        <h3><?= $pantalla ? "Editar Pantalla" : "Nueva Pantalla" ?></h3>
        <form action="controllers/pantalla_guardar.php" method="POST">
            <?php if ($pantalla): ?>
                <label>ID de Pantalla:</label>
                <input type="text" value="<?= $pantalla['id_pantalla'] ?>" disabled style="background: #eee; font-weight: bold;">
                <input type="hidden" name="id_pantalla" value="<?= $pantalla['id_pantalla'] ?>">
            <?php endif; ?>

            <label>Nombre:</label>
            <input type="text" name="nombre_pantalla" value="<?= htmlspecialchars($pantalla['nombre_pantalla'] ?? '') ?>" required>

            <div style="margin-top: 10px;">
                <button type="submit"><?= $pantalla ? "Actualizar" : "Guardar" ?></button>
                <a href="gestion_pantallas.php" style="margin-left: 10px;">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
let pagina = 1;
let terminoBusqueda = '';
let cargando = false;

// Recuperar búsqueda si viene en la URL
const params = new URLSearchParams(window.location.search);
if (params.has('search')) {
    terminoBusqueda = params.get('search');
    document.getElementById('buscador').value = terminoBusqueda;
}

function cargarPantallas(reset = false) {
    if (cargando) return;
    cargando = true;

    const lista = document.getElementById('lista-pantallas');
    fetch(`controllers/buscar_pantallas.php?busqueda=${encodeURIComponent(terminoBusqueda)}&pagina=${pagina}`)
        .then(res => res.json())
        .then(pantallas => {
            if (reset) lista.innerHTML = '';
            if (pantallas.length === 0 && pagina === 1) {
                lista.innerHTML = '<li>🔍 No se encontraron pantallas.</li>';
            } else {
                const activo = new URLSearchParams(window.location.search).get("id");
                pantallas.forEach(p => {
                    const li = document.createElement('li');
                    li.className = "cliente-item" + (p.id_pantalla == activo ? " activo" : "");
                    li.innerHTML = `<a href="gestion_pantallas.php?id=${p.id_pantalla}&search=${encodeURIComponent(terminoBusqueda)}">
                                        ${p.nombre_pantalla}
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
    cargarPantallas(true);
});

document.getElementById('lista-pantallas').addEventListener('scroll', () => {
    const lista = document.getElementById('lista-pantallas');
    if (lista.scrollTop + lista.clientHeight >= lista.scrollHeight - 10) {
        pagina++;
        cargarPantallas();
    }
});

cargarPantallas();
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
