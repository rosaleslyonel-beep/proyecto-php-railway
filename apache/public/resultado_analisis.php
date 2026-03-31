<?php
session_start();
require_once "config/helpers.php";
require_once "config/database.php";

if (!isset($_GET['id_muestra'], $_GET['id_analisis'])) {
    echo "<p>⚠️ Error: Faltan parámetros.</p>";
    exit();
}

$id_muestra = (int) $_GET['id_muestra'];
$id_analisis = (int) $_GET['id_analisis'];

$stmt = $conexion->prepare("
    SELECT a.nombre_estudio, a.tipo_formulario, m.tipo_muestra, m.id_protocolo
    FROM muestra_analisis ma
    JOIN analisis_laboratorio a ON ma.id_analisis = a.id_analisis
    JOIN muestras m ON ma.id_muestra = m.id_muestra
    WHERE ma.id_muestra = ? AND ma.id_analisis = ?
");
$stmt->execute([$id_muestra, $id_analisis]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    echo "<p>⚠️ No se encontró la muestra o el análisis.</p>";
    exit();
}

$id_protocolo = (int)$datos['id_protocolo'];
$tipo_formulario = $datos['tipo_formulario'] ?? 'generico';
$nombre_estudio = $datos['nombre_estudio'];
$tipo_muestra = $datos['tipo_muestra'];

include "views/header.php";
include "views/menu.php";
?>
<div id="contenido">
    <h2>Resultado de Análisis: <?= htmlspecialchars($nombre_estudio) ?></h2>
    <p><strong>Muestra:</strong> <?= htmlspecialchars($tipo_muestra) ?> (ID <?= $id_muestra ?>)</p>

    <?php if (!empty($_GET['error'])): ?>
        <div style="background:#f8d7da; border:1px solid #f5c2c7; color:#842029; padding:12px; border-radius:6px; margin:15px 0;">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <?php
        $form_path = "formularios_resultado/form_resultado_" . strtolower($tipo_formulario) . ".php";
        if (file_exists($form_path)) {
            include $form_path;
        } else {
            echo "<p>⚠️ No hay un formulario configurado para el tipo <strong>$tipo_formulario</strong>.</p>";
        }
        ?>
    </div>
</div>
<?php include "views/footer.php"; ?>
