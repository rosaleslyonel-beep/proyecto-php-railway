<?php
require_once "config/helpers.php";
$termino = $_GET['q'] ?? '';

$stmt = $conexion->prepare("SELECT id_analisis, nombre_estudio, precio FROM analisis_laboratorio 
                            WHERE LOWER(nombre_estudio) LIKE LOWER(?) ORDER BY nombre_estudio LIMIT 50");
$stmt->execute(["%$termino%"]);
$resultados = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Buscar Análisis</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial; padding: 10px; }
        input { width: 100%; padding: 8px; margin-bottom: 10px; }
        .resultado { padding: 8px; border-bottom: 1px solid #ccc; cursor: pointer; }
        .resultado:hover { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <input type="text" id="busqueda" placeholder="Buscar análisis..." oninput="buscar()">
    <div id="lista-resultados">
        <?php foreach ($resultados as $a): ?>
            <div class="resultado" onclick="seleccionar(<?= $a['id_analisis'] ?>, '<?= htmlspecialchars($a['nombre_estudio']) ?>', <?= $a['precio'] ?>)">
                <strong><?= htmlspecialchars($a['nombre_estudio']) ?></strong><br>
                Q <?= number_format($a['precio'], 2) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function seleccionar(id, nombre, precio) {
            if (window.opener && typeof window.opener.agregarAnalisisDesdePopup === 'function') {
                window.opener.agregarAnalisisDesdePopup(id, nombre, precio);
                window.close();
            }
        }

        function buscar() {
            const q = document.getElementById('busqueda').value;
            window.location = "buscar_analisis.php?q=" + encodeURIComponent(q);
        }

        document.getElementById('busqueda').focus();
    </script>
</body>
</html>
