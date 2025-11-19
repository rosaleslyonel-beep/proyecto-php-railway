<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}
require_once "config/database.php";
include "views/header.php";
include "views/menu.php";  
?>

<h2>Ingreso de Muestras para Necropsias - Página 2</h2>
<form action="controllers/ingreso_muestra.php" method="POST">

    <!-- Estado de las Aves -->
    <label>Estado de las Aves:</label>
    <label><input type="checkbox" name="buen_estado"> Buen estado</label>
    <label><input type="checkbox" name="autolisis"> Autolisis</label>

    <!-- Entrega de Resultados -->
    <label>Entrega de Resultados:</label>
    <label><input type="checkbox" name="entrega_personal"> Personal</label>
    <label><input type="checkbox" name="entrega_correo"> Correo Electrónico</label>
    <input type="email" name="correo_resultado" placeholder="Correo Electrónico">

    <!-- Lesiones de la Necropsia -->
    <label>Lesiones de la Necropsia:</label>
    <textarea name="lesiones_necropsia" rows="4"></textarea>

    <!-- Exámenes Realizados -->
    <label>Exámenes Realizados:</label>
    <label><input type="checkbox" name="bacteriologia"> Bacteriología</label>
    <label><input type="checkbox" name="virologia"> Virología</label>
    <label><input type="checkbox" name="serologia"> Serología</label>
    <label><input type="checkbox" name="parasitologico"> Parasitológico</label>
    <label><input type="checkbox" name="histologico"> Histológico</label>
    <label><input type="checkbox" name="micologico"> Micológico</label>

    <!-- Diagnóstico de la Necropsia -->
    <label>Diagnóstico de la Necropsia:</label>
    <textarea name="diagnostico_necropsia" rows="3"></textarea>
    <label>Responsable:</label>
    <input type="text" name="responsable">

    <button type="submit">Registrar Muestra</button>
</form>

<?php include "views/footer.php"; ?>
