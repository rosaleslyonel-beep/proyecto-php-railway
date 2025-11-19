<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();

if (!verificarPermiso(5, 'consultar')) {
    header("Location: dashboard.php"); // o muestra mensaje
    exit();
}

$id_rol = $_GET['id'] ?? null;
$rolData = null;

if ($id_rol) {
    $stmt = $conexion->prepare("SELECT * FROM roles WHERE id_rol = :id");
    $stmt->execute([':id' => $id_rol]);
    $rolData = $stmt->fetch();
}

include "views/header.php";
include "views/menu.php";
?>

<div  id="main-content">
    <!-- Panel izquierdo -->
    <div  id="panel-lista">
        <h3>Roles del Sistema</h3>
        <input type="text" id="buscador" placeholder="Buscar rol..." style="width: 100%; margin-bottom: 10px;">
        <ul id="lista-roles" style="list-style: none; padding: 0; height: 70vh; overflow-y: auto; border: 1px solid #ccc;"></ul>
    </div>

    <!-- Panel derecho -->
    <div  id="panel-detalle">
        <h3><?= $rolData ? "Editar Rol" : "Nuevo Rol" ?></h3>
        <form action="controllers/rol_guardar.php" method="POST">
            <?php if ($rolData): ?>
                <label>ID del Rol:</label>
                <input type="text" value="<?= $rolData['id_rol'] ?>" disabled style="background: #eee; font-weight: bold;">
                <input type="hidden" name="id_rol" value="<?= $rolData['id_rol'] ?>">
            <?php endif; ?>

            <label>Nombre del Rol:</label>
            <input type="text" name="nombre_rol" value="<?= htmlspecialchars($rolData['nombre_rol'] ?? '') ?>" required>

            <div style="margin-top: 10px;">
                <button type="submit"><?= $rolData ? "Actualizar" : "Guardar" ?></button>
                <a href="gestion_roles.php" style="margin-left: 10px;">Cancelar</a>
            </div>
        </form>

        <?php if ($rolData): ?>
            <div style="margin-top: 20px;">
                <a href="asignar_pantallas.php?id_rol=<?= $rolData['id_rol'] ?>&rol=<?= urlencode($rolData['nombre_rol']) ?>">
                    🔒 Asignar Pantallas a este Rol
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let pagina = 1;
let terminoBusqueda = '';
let cargando = false;

const params = new URLSearchParams(window.location.search);
if (params.has('search')) {
    terminoBusqueda = params.get('search');
    document.getElementById('buscador').value = terminoBusqueda;
}

function cargarRoles(reset = false) {
    if (cargando) return;
    cargando = true;

    const lista = document.getElementById('lista-roles');
    fetch(`controllers/buscar_roles.php?busqueda=${encodeURIComponent(terminoBusqueda)}&pagina=${pagina}`)
        .then(res => res.json())
        .then(roles => {
            if (reset) lista.innerHTML = '';
            if (roles.length === 0 && pagina === 1) {
                lista.innerHTML = '<li>🔍 No se encontraron roles.</li>';
            } else {
                const activo = new URLSearchParams(window.location.search).get("id");
                roles.forEach(r => {
                    const li = document.createElement('li');
                    li.className = "cliente-item" + (r.id_rol == activo ? " activo" : "");
                    li.innerHTML = `<a href="gestion_roles.php?id=${r.id_rol}&search=${encodeURIComponent(terminoBusqueda)}">
                                        ${r.nombre_rol}
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
    cargarRoles(true);
});

document.getElementById('lista-roles').addEventListener('scroll', () => {
    const lista = document.getElementById('lista-roles');
    if (lista.scrollTop + lista.clientHeight >= lista.scrollHeight - 10) {
        pagina++;
        cargarRoles();
    }
});

cargarRoles();
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
