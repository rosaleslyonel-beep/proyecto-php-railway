<?php
session_start();
require_once "config/helpers.php";
require_once "config/database.php";

if (!isset($_SESSION["usuario"]) || !isset($_GET["id"])) {
    header("Location: lista_protocolos.php");
    exit();
}

$id_protocolo = $_GET["id"];
include "views/header.php";
include "views/menu.php";

try {
    $stmt = $conexion->prepare("SELECT * FROM protocolos WHERE id_protocolo = :id");
    $stmt->bindParam(":id", $id_protocolo);
    $stmt->execute();
    $protocolo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$protocolo) {
        echo "<p>Protocolo no encontrado.</p>";
        exit();
    }
} catch (PDOException $e) {
    echo "<p>Error al cargar el protocolo.</p>";
    exit();
}
?>
<div id="main-content" class="main-content">
<h2>Detalle del Protocolo</h2>

<div class="protocolo-detail-container">
    <p><strong>Protocolo No.:</strong> <?php echo $protocolo['protocolo_no']; ?></p>
    <p><strong>Cliente:</strong> <?php echo $protocolo['id_cliente']; ?></p>
    <p><strong>Fecha:</strong> <?php echo $protocolo['fecha']; ?></p>
    <p><strong>Tipo de Protocolo:</strong> <?php echo $protocolo['id_tipo_protocolo']; ?></p>
    <p><strong>Estado de la Muestra:</strong> <?php echo $protocolo['estado_muestra']; ?></p>
    <p><strong>Teléfono:</strong> <?php echo $protocolo['telefono']; ?></p>
    <p><strong>Correo Electrónico:</strong> <?php echo $protocolo['correo']; ?></p>
    <p><strong>Dirección:</strong> <?php echo $protocolo['direccion']; ?></p>
    <p><strong>Observaciones:</strong> <?php echo $protocolo['observaciones']; ?></p>

    <?php if (!empty($protocolo['firma_imagen'])): ?>
        <p><strong>Firma:</strong></p>
        <img src="<?php echo $protocolo['firma_imagen']; ?>" alt="Firma" width="200">
    <?php endif; ?>
</div>
</div>
<?php include "views/footer.php"; ?>
</div>
