<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();

if (!verificarPermiso(14, 'consultar')) {
    header("Location: dashboard.php"); // o muestra mensaje
    exit();
}

$id_tipo = $_GET['id'] ?? null;
$tipo = null;

if ($id_tipo) {
    $stmt = $conexion->prepare("SELECT * FROM tipos_protocolo WHERE id_tipo_protocolo = :id");
    $stmt->execute([':id' => $id_tipo]);
    $tipo = $stmt->fetch();
}

include "views/header.php";
include "views/menu.php";
?>

<div class="main-content" style="display: flex; height: 90vh;">
    <!-- Panel izquierdo: Lista -->
    <div style="width: 30%; border-right: 1px solid #ccc; padding: 10px;">
        <h3>Tipos de Protocolo</h3>
        <input type="text" id="buscador" placeholder="Buscar tipo..." style="width: 100%; margin-bottom: 10px;">
        <ul id="lista-tipos" style="list-style: none; padding: 0; height: 70vh; overflow-y: auto; border: 1px solid #ccc;"></ul>
    </div>

    <!-- Panel derecho: Formulario -->
    <div style="width: 70%; padding: 20px;">
        <h3><?= $tipo ? "Editar Tipo de Protocolo" : "Nuevo Tipo de Protocolo" ?></h3>
        <form action="controllers/tipo_protocolo_guardar.php" method="POST">
            <?php if ($tipo): ?>
                <label>ID del Tipo de Protocolo:</label>
                <input type="text" value="<?= $tipo['id_tipo_protocolo'] ?>" disabled style="background: #eee; font-weight: bold;">
                <input type="hidden" name="id_tipo_protocolo" value="<?= $tipo['id_tipo_protocolo'] ?>">
            <?php endif; ?>

            <label>Nombre del Tipo:</label>
            <input type="text" name="nombre_tipo" value="<?= htmlspecialchars($tipo['nombre_tipo'] ?? '') ?>" required>

            <label>Prefijo para correlativo:</label>
            <input type="text" name="prefijo" maxlength="10" value="<?= htmlspecialchars($tipo['prefijo'] ?? '') ?>" required>

            <div style="margin-top: 10px;">
                <button type="submit"><?= $tipo ? "Actualizar" : "Guardar" ?></button>
                <a href="gestion_tipos_protocolo.php" style="margin-left: 10px;">Cancelar</a>
            </div>
        </form>
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

function cargarTipos(reset = false) {
    if (cargando) return;
    cargando = true;

    const lista = document.getElementById('lista-tipos');
    fetch(`controllers/buscar_tipos_protocolo.php?busqueda=${encodeURIComponent(terminoBusqueda)}&pagina=${pagina}`)
        .then(res => res.json())
        .then(tipos => {
            if (reset) lista.innerHTML = '';
            if (tipos.length === 0 && pagina === 1) {
                lista.innerHTML = '<li>🔍 No se encontraron tipos.</li>';
            } else {
                const activo = new URLSearchParams(window.location.search).get("id");
                tipos.forEach(t => {
                    const li = document.createElement('li');
                    li.className = "cliente-item" + (t.id_tipo_protocolo == activo ? " activo" : "");
                    li.innerHTML = `<a href="gestion_tipos_protocolo.php?id=${t.id_tipo_protocolo}&search=${encodeURIComponent(terminoBusqueda)}">
                                        ${t.nombre_tipo} (${t.prefijo})
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
    cargarTipos(true);
});

document.getElementById('lista-tipos').addEventListener('scroll', () => {
    const lista = document.getElementById('lista-tipos');
    if (lista.scrollTop + lista.clientHeight >= lista.scrollHeight - 10) {
        pagina++;
        cargarTipos();
    }
});

cargarTipos();
</script>

<style>
 
</style>

<?php include "views/footer.php"; ?>
