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
 

// Obtener datos existentes si hay
$stmt = $conexion->prepare("SELECT datos_json, observaciones FROM resultados_analisis WHERE id_muestra = :id_muestra AND id_analisis = :id_analisis");
$stmt->execute([':id_muestra' => $id_muestra, ':id_analisis' => $id_analisis]);
$datos_resultado = $stmt->fetch(PDO::FETCH_ASSOC);
$datos_json = $datos_resultado ? json_decode($datos_resultado['datos_json'], true) : [];
$observaciones = $datos_resultado['observaciones'] ?? '';

function renderPlaca($indice, $datos)
{
    $val = function($key) use ($datos) {
        return htmlspecialchars($datos[$key] ?? '');
    };
    return "
    <div class='placa'>
        <input type='hidden' name='placas[$indice][tipo]' value='IDIA'>
        <div class='hex-row'>
            <div class='hex'><input name='placas[$indice][C1]' value='" . $val('C1') . "' placeholder='C1'></div>
            <div class='hex'><input name='placas[$indice][C2]' value='" . $val('C2') . "' placeholder='C2'></div>
            <div class='hex'><input name='placas[$indice][C3]' value='" . $val('C3') . "' placeholder='C3'></div>
        </div>
        <div class='hex-row'>
            <div class='hex'><input name='placas[$indice][C4]' value='" . $val('C4') . "' placeholder='C4'></div>
            <div class='hex'><input name='placas[$indice][A]' value='" . $val('A') . "' placeholder='A'></div>
            <div class='hex'><input name='placas[$indice][C5]' value='" . $val('C5') . "' placeholder='C5'></div>
        </div>
        <div class='hex-row-center'>
            <div class='hex'><input name='placas[$indice][C6]' value='" . $val('C6') . "' placeholder='C6'></div>
        </div>
        <button type='button' onclick='eliminarPlaca(this)' class='btn-delete'>🗑️ Eliminar placa</button>
    </div>";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Resultado IDIA</title>
    <style>
        .placa {
            border: 1px solid #ccc;
            margin-bottom: 15px;
            padding: 10px;
        }
        .hex-row, .hex-row-center {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        .hex input {
            width: 50px;
            height: 50px;
            text-align: center;
            clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
            background: #eef;
            border: 1px solid #999;
            outline: none;
        }
        .btn-delete {
            margin-top: 5px;
            color: red;
        }
        .botones-top {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h2>Resultado: Inmunodifusión en Agar Gel (IDIA)</h2>

    <div class='botones-top'>
        <button onclick="window.location.href='gestion_protocolos.php?tab=tab_resultados&id=<?= $id_protocolo ?>'">
            ⬅️ Volver
        </button>
    </div>

    <form method='POST' action='guardar_resultado_idia.php'>
        <input type='hidden' name='id_muestra' value='<?= $id_muestra ?>'>
        <input type='hidden' name='id_analisis' value='<?= $id_analisis ?>'>
        <div id='contenedor-placas'>
            <?php
            if (!empty($datos_json['placas'])) {
                foreach ($datos_json['placas'] as $i => $placa) {
                    echo renderPlaca($i, $placa);
                }
            } else {
                echo renderPlaca(0, []);
            }
            ?>
        </div>

        <button type='button' onclick='agregarPlaca()'>➕ Agregar otra placa</button>

        <br><br>
        <label>Observaciones:</label><br>
        <textarea name='observaciones' rows='4' cols='50'><?= htmlspecialchars($observaciones) ?></textarea><br><br>

        <button type='submit'>💾 Guardar Resultado</button>
    </form>

    <script>
        let totalPlacas = <?= !empty($datos_json['placas']) ? count($datos_json['placas']) : 1 ?>;

        function agregarPlaca() {
            const contenedor = document.getElementById('contenedor-placas');
            const nuevaPlaca = `<?= str_replace("
", "", renderPlaca("IDX", [])) ?>`.replace(/\[IDX\]/g, `[${totalPlacas}]`).replace(/IDX/g, totalPlacas);
            const temp = document.createElement('div');
            temp.innerHTML = nuevaPlaca;
            contenedor.appendChild(temp);
            totalPlacas++;
        }

        function eliminarPlaca(btn) {
            const placa = btn.closest('.placa');
            if (placa) placa.remove();
        }
    </script>
</body>
</html>