<?php
require_once "config/helpers.php";
require_once "config/database.php";
$id_muestra = $_GET['id_muestra'] ?? null;
$id_analisis = $_GET['id_analisis'] ?? null;
$id_protocolo = $_GET['id_protocolo'] ?? null;
if (!$id_muestra || !$id_analisis) {
    echo "Error: Faltan parámetros.";
    exit;
}
 

// Obtener resultado existente
$datos_guardados = [];
if ($id_muestra && $id_analisis) {
    $stmt = $conexion->prepare("SELECT * FROM resultados_analisis WHERE id_muestra = ? AND id_analisis = ?");
    $stmt->execute([$id_muestra, $id_analisis]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resultado) {
        $datos_guardados = json_decode($resultado['datos_json'], true);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resultado IDIA</title>
    <style>
        .placa-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .placa {
            width: 100px;
            height: 100px;
            clip-path: polygon(50% 0%, 93% 25%, 93% 75%, 50% 100%, 7% 75%, 7% 25%);
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
        }
        .placa.positivo {
            background-color: #e57373;
            color: white;
        }
        .placa.negativo {
            background-color: #81c784;
            color: white;
        }
    </style>
    <script>
        function toggleResultado(btn) {
            if (btn.classList.contains('positivo')) {
                btn.classList.remove('positivo');
                btn.classList.add('negativo');
                btn.dataset.valor = 'negativo';
                btn.innerText = '−';
            } else {
                btn.classList.remove('negativo');
                btn.classList.add('positivo');
                btn.dataset.valor = 'positivo';
                btn.innerText = '+';
            }
        }

        function agregarPlaca() {
            const container = document.getElementById('placas');
            const index = container.children.length + 1;
            const div = document.createElement('div');
            div.className = 'placa negativo';
            div.dataset.valor = 'negativo';
            div.innerText = '−';
            div.onclick = function () { toggleResultado(this); };
            container.appendChild(div);
        }

        function regresar() {
            window.location.href = 'gestion_protocolos.php?tab=tab_resultados&id=' + <?= json_encode($id_muestra) ?>;
        }

        function prepararEnvio() {
            const placas = document.querySelectorAll('.placa');
            const resultados = Array.from(placas).map(p => p.dataset.valor);
            document.getElementById('placas_data').value = JSON.stringify(resultados);
        }

        window.onload = function () {
            const valores = <?= json_encode($datos_guardados['placas'] ?? []) ?>;
            const container = document.getElementById('placas');
            for (let val of valores) {
                const div = document.createElement('div');
                div.className = 'placa ' + (val === 'positivo' ? 'positivo' : 'negativo');
                div.dataset.valor = val;
                div.innerText = val === 'positivo' ? '+' : '−';
                div.onclick = function () { toggleResultado(this); };
                container.appendChild(div);
            }
        }
    </script>
</head>
<body>
    <h2>Resultado IDIA</h2>
    <form method="POST" action="guardar_resultado_idia.php" onsubmit="prepararEnvio()">
        <input type="hidden" name="id_muestra" value="<?= htmlspecialchars($id_muestra) ?>">
        <input type="hidden" name="id_analisis" value="<?= htmlspecialchars($id_analisis) ?>">
        <input type="hidden" name="placas" id="placas_data">
        <label>Observaciones:</label><br>
        <textarea name="observaciones" rows="3" cols="50"><?= htmlspecialchars($datos_guardados['observaciones'] ?? '') ?></textarea><br><br>

        <h4>Placas:</h4>
        <div id="placas" class="placa-container"></div>
        <br>
        <button type="button" onclick="agregarPlaca()">Agregar Placa</button>
        <br><br>
        <button type="submit">Guardar</button>
        <button type="button" onclick="regresar()">Regresar</button>
    </form>
</body>
</html>
