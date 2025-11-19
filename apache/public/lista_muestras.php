<?php
session_start();
require_once "config/helpers.php";

if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] !== "admin") {
    header("Location: index.php");
    exit();
}

include "views/header.php";
include "views/menu.php";
?>
<h2>Lista de Muestras Registradas</h2>

<?php
if (isset($_GET["success"])) {
    echo '<div class="success-msg">Muestra registrada exitosamente.</div>';
}

// Consulta corregida para listar muestras con clientes asociados
$stmt = $conexion->query("SELECT m.id_muestra, m.codigo_muestra, m.tipo_muestra, m.estado_muestra, c.nombre AS cliente
                         FROM muestras m
                         JOIN clientes c ON m.id_cliente = c.id_cliente");
$muestras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
 

<table>
    <tr>
        <th>ID</th>
        <th>Código</th>
        <th>Tipo</th>
        <th>Estado</th>
        <th>Cliente</th>
    </tr>
    <?php foreach ($muestras as $muestra) { ?>
        <tr>
            <td><?php echo $muestra["id_muestra"]; ?></td>
            <td><?php echo $muestra["codigo_muestra"]; ?></td>
            <td><?php echo $muestra["tipo_muestra"]; ?></td>
            <td><?php echo $muestra["estado_muestra"]; ?></td>
            <td><?php echo $muestra["cliente"]; ?></td>
        </tr>
    <?php } ?>
</table>

<?php include "views/footer.php"; ?>
