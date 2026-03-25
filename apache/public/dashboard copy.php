<?php
require_once "config/session.php";
require_once "config/helpers.php";
?>

<?php
require_once "config/session.php";
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

require_once "config/helpers.php";
include "views/header.php";
include "views/menu.php";
?>
<div id="main-content" >

<h2>Dashboard de Administración</h2>
<p>Bienvenido, <?php echo $_SESSION["usuario"]["nombre"]; ?> (Rol: <?php echo ucfirst($_SESSION["usuario"]["rol_nombre"]); ?>)</p>
</div>
<?php include "views/footer.php"; ?>
