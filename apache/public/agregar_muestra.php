<?php
require_once "config/database.php";
require_once "config/helpers.php";
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id_protocolo = $_GET['id_protocolo'] ?? null;
if (!$id_protocolo || !is_numeric($id_protocolo)) {
    echo "ID de protocolo no válido.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];
    $analisis = $_POST['analisis'];
    $precio = $_POST['precio'];
    $id_usuario = $_SESSION['usuario']['id_usuario'] ?? 1;
    $fecha_hoy = date("Y-m-d H:i:s");

    $stmt = $conexion->prepare("
        INSERT INTO muestras (id_protocolo, codigo, analisis, precio, created_by, created_date)
        VALUES (:id_protocolo, :codigo, :analisis, :precio, :creado_por, :fecha)
    ");
    $stmt->execute([
        ':id_protocolo' => $id_protocolo,
        ':codigo' => $codigo,
        ':analisis' => $analisis,
        ':precio' => $precio,
        ':creado_por' => $id_usuario,
        ':fecha' => $fecha_hoy
    ]);

    header("Location: gestion_protocolos.php?id=$id_protocolo&tab=muestras");
    exit();
}

include "views/header.php";
include "views/menu.php";
?>

<div class="main-content" style="padding: 20px;">
    <h3>Agregar Muestra al Protocolo #<?= $id_protocolo ?></h3>

    <form method="POST">
        <input type="hidden" name="id_protocolo" value="<?= $id_protocolo ?>">

        <label>Código de Muestra:</label>
        <input type="text" name="codigo" required>

        <label>Análisis a Realizar:</label>
        <input type="text" name="analisis" required>

        <label>Precio (Q):</label>
        <input type="number" name="precio" step="0.01" required>

        <div style="margin-top: 10px;">
            <button type="submit">Guardar Muestra</button>
            <a href="gestion_protocolos.php?id=<?= $id_protocolo ?>&tab=muestras" style="margin-left: 10px;">Cancelar</a>
        </div>
    </form>
</div>

<?php include "views/footer.php"; ?>
