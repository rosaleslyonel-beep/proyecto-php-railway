<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();

if (!verificarPermiso(5, 'modificar')) {
    header("Location: dashboard.php"); // o muestra mensaje
    exit();
}
$id_rol = $_GET['id_rol'] ?? null;
$nombre_rol = $_GET['rol'] ?? '';

$stmtPantallas = $conexion->query("SELECT id_pantalla, nombre_pantalla FROM pantallas ORDER BY nombre_pantalla");
$pantallas = $stmtPantallas->fetchAll();

$stmtPermisos = $conexion->prepare("SELECT * FROM permisos WHERE id_rol = :id_rol");
$stmtPermisos->execute([':id_rol' => $id_rol]);
$permisos_actuales = [];
foreach ($stmtPermisos->fetchAll() as $perm) {
    $permisos_actuales[$perm['id_pantalla']] = $perm;
}

include "views/header.php";
include "views/menu.php";
?>

<div class="main-content" style="padding: 20px;">
    <h2>Asignar Permisos al Rol: <?= htmlspecialchars($nombre_rol) ?></h2>

    <form method="POST" action="controllers/asignar_pantallas_guardar.php">
        <input type="hidden" name="id_rol" value="<?= $id_rol ?>">

        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Pantalla</th>
                    <th>Consultar</th>
                    <th>Agregar</th>
                    <th>Modificar</th>
                    <th>Eliminar</th>
                </tr>
            </thead>
            <tbody> 
                <?php foreach ($pantallas as $p): 
                    $perm = $permisos_actuales[$p['id_pantalla']] ?? [];
                ?>
                <tr>
                    <td><?= htmlspecialchars($p['nombre_pantalla']) ?></td>
                    <td><input type="checkbox" name="permisos[<?= $p['id_pantalla'] ?>][consultar]" <?= !empty($perm['consultar']) ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="permisos[<?= $p['id_pantalla'] ?>][agregar]" <?= !empty($perm['agregar']) ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="permisos[<?= $p['id_pantalla'] ?>][modificar]" <?= !empty($perm['modificar']) ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="permisos[<?= $p['id_pantalla'] ?>][eliminar]" <?= !empty($perm['eliminar']) ? 'checked' : '' ?>></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 15px;">
            <button type="submit">Guardar Permisos</button>
            <a href="gestion_roles.php">← Regresar</a>
        </div>
    </form>
</div>

<?php include "views/footer.php"; ?>
