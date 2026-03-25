<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();


if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$rol = $_SESSION['usuario']['rol_nombre'];
$id_cliente = $rol === 'cliente'
    ? $_SESSION['usuario']['id_cliente']
    : ($_GET['id_cliente'] ?? null);
    $nombre_cliente = '';
    if ($id_cliente) {
        $stmtCliente = $conexion->prepare("SELECT nombre FROM clientes WHERE id_cliente = :id");
        $stmtCliente->execute([':id' => $id_cliente]);
        $nombre_cliente = $stmtCliente->fetchColumn();
    }
if (!$id_cliente) {
    echo "<p>⚠️ No se ha especificado cliente.</p>";
    exit();
}

$id_finca = $_GET['id'] ?? null;
$finca = null;

if ($id_finca) {
    $stmt = $conexion->prepare("SELECT * FROM fincas WHERE id_finca = :id AND id_cliente = :id_cliente");
    $stmt->execute([':id' => $id_finca, ':id_cliente' => $id_cliente]);
    $finca = $stmt->fetch();
}

include "views/header.php";
include "views/menu.php";
?>

<div class="main-content" style="display: flex; height: 90vh;">
    <!-- Panel izquierdo: Lista de fincas -->
    <div style="width: 30%; border-right: 1px solid #ccc; padding: 10px;">
        <h3>Fincas del Cliente</h3>
        <input type="text" id="buscador" placeholder="Buscar finca..." style="width: 100%; margin-bottom: 10px;">
        <ul id="lista-fincas" style="list-style: none; padding: 0; height: 70vh; overflow-y: auto; border: 1px solid #ccc;"></ul>
    </div>

    <!-- Panel derecho: Formulario -->
    <div style="width: 70%; padding: 20px;">
    <h2>Fincas de: <?= htmlspecialchars($nombre_cliente) ?></h2>

<?php if ($rol === 'admin'): ?>
    <a href="gestion_clientes.php?id=<?= $id_cliente ?>" style="display:inline-block; margin-bottom: 15px;">
        ← Volver al Cliente
    </a>
<?php endif; ?>
        <h3><?= $finca ? "Editar Finca" : "Nueva Finca" ?></h3>
        <form action="controllers/finca_guardar.php" method="POST">
            <?php if ($finca): ?>
                <input type="hidden" name="id_finca" value="<?= $finca['id_finca'] ?>">
            <?php endif; ?>
            <input type="hidden" name="id_cliente" value="<?= $id_cliente ?>">

            <label>Nombre de la Finca:</label>
            <input type="text" name="nombre_finca" value="<?= htmlspecialchars($finca['nombre_finca'] ?? '') ?>" required>

            <label>Ubicación:</label>
            <input type="text" name="ubicacion" value="<?= htmlspecialchars($finca['ubicacion'] ?? '') ?>" required>

            <div style="margin-top: 10px;">
                <button type="submit"><?= $finca ? "Actualizar" : "Guardar" ?></button>
                <a href="gestion_fincas.php?id_cliente=<?= $id_cliente ?>" style="margin-left: 10px;">Cancelar</a>
            </div>
        </form>
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
    fetch(`controllers/buscar_fincas.php?busqueda=${encodeURIComponent(terminoBusqueda)}&pagina=${pagina}&id_cliente=<?= $id_cliente ?>`)
        .then(res => res.json())
        .then(fincas => {
            if (reset) lista.innerHTML = '';
            if (fincas.length === 0 && pagina === 1) {
                lista.innerHTML = '<li>🔍 No se encontraron fincas.</li>';
            } else {
                const fincaActiva = new URLSearchParams(window.location.search).get("id");
                fincas.forEach(f => {
                    const li = document.createElement('li');
                    li.className = "cliente-item" + (f.id_finca == fincaActiva ? " activo" : "");
                    li.innerHTML = `<a href="gestion_fincas.php?id=${f.id_finca}&id_cliente=<?= $id_cliente ?>">
                                        ${f.nombre_finca}
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
    cargarFincas(true);
});

document.getElementById('lista-fincas').addEventListener('scroll', () => {
    const lista = document.getElementById('lista-fincas');
    if (lista.scrollTop + lista.clientHeight >= lista.scrollHeight - 10) {
        pagina++;
        cargarFincas();
    }
});

cargarFincas();
</script>

<style>
 
</style>

<?php include "views/footer.php"; ?>
