<?php
require_once "config/session.php";
require_once "config/helpers.php";
?>
<div id="main-content" class="main-content">
<?php
require_once "config/helpers.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

include "views/header.php";
include "views/menu.php";
?>

<div class="content">
<h2>Lista de Protocolos</h2>

<div class="protocolos-list-container">
    <table>
        <tr>
            <th>Protocolo No.</th>
            <th>Cliente</th>
            <th>Tipo de Protocolo</th>
            <th>Fecha</th>
            <th>Estado de la Muestra</th>
            <th>Acciones</th>
        </tr>
        <?php
        try {
            $stmt = $conexion->query("SELECT id_protocolo, protocolo_no, id_cliente, id_tipo_protocolo, fecha, estado_muestra FROM protocolos ORDER BY fecha DESC");
            while ($protocolo = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>
                        <td>{$protocolo['protocolo_no']}</td>
                        <td>{$protocolo['id_cliente']}</td>
                        <td>{$protocolo['id_tipo_protocolo']}</td>
                        <td>{$protocolo['fecha']}</td>
                        <td>{$protocolo['estado_muestra']}</td>
                        <td><a href='ver_protocolo.php?id={$protocolo['id_protocolo']}' class='view-btn'>Ver Detalle</a></td>
                      </tr>";
            }
        } catch (PDOException $e) {
            echo "<tr><td colspan='6'>Error al cargar los protocolos.</td></tr>";
        }
        ?>
    </table>
</div>
</div>
<?php include "views/footer.php"; ?>
</div>