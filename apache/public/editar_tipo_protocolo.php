<?php
require_once "config/helpers.php";
require_once "config/database.php";
session_start();

if (!isset($_SESSION['usuario']) || !isset($_GET['id'])) {
    header("Location: gestion_tipos_protocolo.php");
    exit();
}

$id = $_GET['id'];

// Obtener datos actuales
$stmt = $conexion->prepare("SELECT * FROM tipos_protocolo WHERE id_tipo_protocolo = :id");
$stmt->execute([':id' => $id]);
$tipo = $stmt->fetch();

if (!$tipo) {
    header("Location: gestion_tipos_protocolo.php?error=no_encontrado");
    exit();
}

include "views/header.php";
include "views/menu.php";
?>

<div class="main-content">
    <h2>Editar Tipo de Protocolo</h2>

    <form action="controllers/tipo_protocolo_actualizar.php" method="POST">
        <input type="hidden" name="id_tipo_protocolo" value="<?= $tipo['id_tipo_protocolo'] ?>">

        <label>Nombre del Tipo:</label>
        <input type="text" name="nombre_tipo" value="<?= htmlspecialchars($tipo['nombre_tipo']) ?>" required>

        <label>Prefijo:</label>
        <input type="text" name="prefijo" value="<?= htmlspecialchars($tipo['prefijo']) ?>" maxlength="10" required>

        <label>Descripción:</label>
        <textarea name="descripcion" rows="3"><?= htmlspecialchars($tipo['descripcion']) ?></textarea>

        <label>Estado:</label>
        <select name="activo">
            <option value="1" <?= $tipo['activo'] ? 'selected' : '' ?>>Activo</option>
            <option value="0" <?= !$tipo['activo'] ? 'selected' : '' ?>>Inactivo</option>
        </select>

        <button type="submit">Actualizar</button>
        <a href="gestion_tipos_protocolo.php">Cancelar</a>
    </form>
</div>

<?php include "views/footer.php"; ?>
