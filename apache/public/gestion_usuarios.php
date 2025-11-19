<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();

if (!verificarPermiso(6, 'consultar')) {
    header("Location: dashboard.php"); // o muestra mensaje
    exit();
}

$id_usuario = $_GET['id'] ?? null;
$usuario = null;

if ($id_usuario) {
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id_usuario = :id");
    $stmt->execute([':id' => $id_usuario]);
    $usuario = $stmt->fetch();
}

// Obtener clientes (por si es usuario tipo cliente)
$clientes = $conexion->query("SELECT id_cliente, nombre FROM clientes ORDER BY nombre")->fetchAll();

include "views/header.php";
include "views/menu.php";
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div  id="main-content"  >
    <!-- Panel izquierdo: Lista -->
    <div  id="panel-lista"  >
        <h3>Usuarios</h3>
        <input type="text" id="buscador" placeholder="Buscar usuario..." style="width: 100%; margin-bottom: 10px;">
        <ul id="lista-usuarios" style="list-style: none; padding: 0; height: 70vh; overflow-y: auto; border: 1px solid #ccc;"></ul>
    </div>

    <!-- Panel derecho: Formulario -->
    <div  id="panel-detalle"  >
        <h3><?= $usuario ? "Editar Usuario" : "Nuevo Usuario" ?></h3>
        <form action="controllers/usuario_guardar.php" method="POST">
            <?php if ($usuario): ?>
                <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
            <?php endif; ?>

            <label>Nombre de Usuario:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>

            <label>Correo:</label>
            <input type="email" name="correo" value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>" required>

            <label>Contraseña: <?= $usuario ? "(dejar en blanco para no cambiar)" : "" ?></label>
            <input type="password" name="contrasena" <?= $usuario ? '' : 'required' ?>>

            <label>Rol:</label>
<select id="select_rol" name="id_rol" style="width: 100%;" required>
    <option value="">-- Seleccione un rol --</option>
    <?php
        $stmt = $conexion->query("SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol");
        while ($rol = $stmt->fetch()) {
            $selected = (isset($usuario['id_rol']) && $usuario['id_rol'] == $rol['id_rol']) ? 'selected' : '';
            echo "<option value='{$rol['id_rol']}' $selected>" . htmlspecialchars($rol['nombre_rol']) . "</option>";
        }
    ?>
</select>

            <label>Estado:</label>
            <select name="estado" required>
                <option value="1" <?= isset($usuario) && $usuario['estado'] == 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= isset($usuario) && $usuario['estado'] == 0 ? 'selected' : '' ?>>Inactivo</option>
            </select>

            <label>Cliente Asociado (solo para rol cliente):</label>
            <select name="id_cliente">
                <option value="">-- Ninguno --</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?= $cliente['id_cliente'] ?>"
                        <?= $usuario && $usuario['id_cliente'] == $cliente['id_cliente'] ? 'selected' : '' ?>>
                        <?= $cliente['nombre'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div style="margin-top: 10px;">
                <button type="submit"><?= $usuario ? "Actualizar" : "Guardar" ?></button>
                <a href="gestion_usuarios.php" style="margin-left: 10px;">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
let pagina = 1;
let terminoBusqueda = '';
let cargando = false;

function cargarUsuarios(reset = false) {
    if (cargando) return;
    cargando = true;

    const lista = document.getElementById('lista-usuarios');
    fetch(`controllers/buscar_usuarios.php?busqueda=${encodeURIComponent(terminoBusqueda)}&pagina=${pagina}`)
        .then(res => res.json())
        .then(usuarios => {
            if (reset) lista.innerHTML = '';
            if (usuarios.length === 0 && pagina === 1) {
                lista.innerHTML = '<li>🔍 No se encontraron usuarios.</li>';
            } else {
                const activo = new URLSearchParams(window.location.search).get("id");
                usuarios.forEach(u => {
                    const li = document.createElement('li');
                    li.className = "cliente-item" + (u.id_usuario == activo ? " activo" : "" );
                    li.innerHTML = `<a href="gestion_usuarios.php?id=${u.id_usuario}&search=${encodeURIComponent(terminoBusqueda)}">
                                        ${u.nombre} (${u.rol})
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
    cargarUsuarios(true);
});

document.getElementById('lista-usuarios').addEventListener('scroll', () => {
    const lista = document.getElementById('lista-usuarios');
    if (lista.scrollTop + lista.clientHeight >= lista.scrollHeight - 10) {
        pagina++;
        cargarUsuarios();
    }
});
const params = new URLSearchParams(window.location.search);
if (params.has('search')) {
    terminoBusqueda = params.get('search');
    document.getElementById('buscador').value = terminoBusqueda;
}   
cargarUsuarios();
</script>
<script>
    $(document).ready(function() {
        $('#select_rol').select2({
            placeholder: "Buscar rol...",
            allowClear: true
        });
    });
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
        background-color: #263238;
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
