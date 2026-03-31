<?php
require_once "config/helpers.php";
require_once "config/database.php";

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$id_muestra = $_GET['id_muestra'] ?? null;
$id_analisis = $_GET['id_analisis'] ?? null;
$id_protocolo = $_GET['id_protocolo'] ?? null;

if (!$id_muestra || !$id_analisis) {
    echo "Error: Faltan parámetros.";
    exit;
}

$stmt = $conexion->prepare("SELECT id_protocolo, cantidad, tipo_muestra FROM muestras WHERE id_muestra = ?");
$stmt->execute([$id_muestra]);
$muestra = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$muestra) {
    echo "Error: No se encontró la muestra.";
    exit;
}
if (!$id_protocolo) {
    $id_protocolo = $muestra['id_protocolo'];
}
$cantidad_muestra = (int)($muestra['cantidad'] ?? 0);
if ($cantidad_muestra <= 0) $cantidad_muestra = 1;

$datos_guardados = [];
$id_resultado_actual = null;
$resultado = null;
$stmt = $conexion->prepare("SELECT * FROM resultados_analisis WHERE id_muestra = ? AND id_analisis = ?");
$stmt->execute([$id_muestra, $id_analisis]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);
if ($resultado) {
    $id_resultado_actual = $resultado['id_resultado'];
    $datos_guardados = json_decode($resultado['datos_json'] ?? '{}', true);
    if (!is_array($datos_guardados)) $datos_guardados = [];
}

$resultado_emitido = false;
if ($id_resultado_actual && $id_protocolo) {
    $stmt = $conexion->prepare("SELECT resultados_incluidos_json FROM protocolo_emisiones_resultados WHERE id_protocolo = ?");
    $stmt->execute([$id_protocolo]);
    $emisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($emisiones as $emision) {
        $ids = json_decode($emision['resultados_incluidos_json'] ?? '[]', true);
        if (is_array($ids) && in_array((int)$id_resultado_actual, array_map('intval', $ids), true)) {
            $resultado_emitido = true;
            break;
        }
    }
}
$soloLectura = $resultado_emitido;

$filas = $datos_guardados['filas'] ?? [];
if (!is_array($filas)) $filas = [];

$lineas = [];
for ($i = 1; $i <= $cantidad_muestra; $i++) {
    $lineas[$i] = [
        'mg' => $filas[$i]['mg'] ?? '',
        'ms' => $filas[$i]['ms'] ?? '',
        'salmonella' => $filas[$i]['salmonella'] ?? '',
        'otra' => $filas[$i]['otra'] ?? '',
    ];
}

function contarResultado($lineas, $campo, $valor) {
    $total = 0;
    foreach ($lineas as $linea) {
        if (($linea[$campo] ?? '') === $valor) $total++;
    }
    return $total;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Prueba rápida en placa</title>
<style>
.wrap{font-family:Arial,sans-serif;max-width:1200px;margin:0 auto;background:#fff;border:1px solid #ddd;border-radius:10px;padding:20px}
.top-actions{margin-bottom:15px}
.btn-regresar{display:inline-block;text-decoration:none;padding:10px 14px;background:#6c757d;color:#fff;border-radius:6px;font-weight:bold}
.bloque-lectura{background:#e2e3e5;border:1px solid #c6c7c8;color:#41464b;padding:12px;margin:15px 0;border-radius:6px}
.titulo{text-align:center;margin:10px 0 20px}
.grid{display:grid;grid-template-columns:repeat(2,minmax(260px,1fr));gap:14px 18px;margin:18px 0}
.campo{display:flex;flex-direction:column;gap:6px}
.campo label{font-weight:bold;color:#333}
.campo input{width:100%;box-sizing:border-box;border:1px solid #ccc;border-radius:6px;padding:10px;font-size:15px}
.tabla-wrap{overflow:auto;margin-top:10px}
table{width:100%;border-collapse:collapse;min-width:980px}
th,td{border:1px solid #888;padding:6px;text-align:center}
thead th{background:#f3f3f3}
th.vertical{writing-mode:vertical-rl;transform:rotate(180deg);white-space:nowrap;min-width:28px;padding:8px 4px}
td:first-child, th:first-child{font-weight:bold}
.radio-cell label{display:inline-flex;align-items:center;justify-content:center;min-width:32px}
.resumen{margin-top:14px;display:flex;gap:14px;flex-wrap:wrap}
.resumen-box{background:#f8f9fa;border:1px solid #ddd;border-radius:8px;padding:10px 12px;min-width:180px}
.acciones-finales{margin-top:18px;display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.btn-guardar{padding:10px 16px;border:none;background:#198754;color:#fff;font-weight:bold;border-radius:6px;cursor:pointer}
.help{background:#fff3cd;border:1px solid #ffe69c;color:#664d03;padding:10px 12px;border-radius:6px;margin:10px 0 16px}
@media (max-width:800px){.grid{grid-template-columns:1fr}}
</style>
<script>
const soloLectura = <?= $soloLectura ? 'true' : 'false' ?>;

function actualizarResumen() {
    const grupos = ['mg','ms','salmonella','otra'];
    grupos.forEach(g => {
        let pos = 0, neg = 0;
        document.querySelectorAll('input[name^="filas"][name$="[' + g + ']"]:checked').forEach(r => {
            if (r.value === 'positivo') pos++;
            if (r.value === 'negativo') neg++;
        });
        const posEl = document.getElementById('res-' + g + '-pos');
        const negEl = document.getElementById('res-' + g + '-neg');
        if (posEl) posEl.textContent = pos;
        if (negEl) negEl.textContent = neg;
    });
}

document.addEventListener('change', function(e){
    if (e.target.matches('input[type="radio"]')) {
        actualizarResumen();
    }
});

window.addEventListener('DOMContentLoaded', actualizarResumen);
</script>
</head>
<body>
<div class="wrap">
    <div class="top-actions">
        <a href="gestion_protocolos.php?id=<?= (int)$id_protocolo ?>&tab=tab_resultados" class="btn-regresar">← Regresar a Resultados</a>
    </div>

    <h2 class="titulo">Prueba rápida en placa</h2>

    <div class="help">
        Se crean automáticamente <?= (int)$cantidad_muestra ?> líneas según la cantidad de la muestra.
        Puede marcar positivo o negativo por cada línea y por cada columna.
    </div>

    <?php if ($soloLectura): ?>
        <div class="bloque-lectura">
            Este resultado ya fue emitido. Solo está disponible para consulta.
            Para modificarlo, debe crear una corrección.
        </div>
    <?php endif; ?>

    <form method="POST" action="guardar_resultado_prueba_rapida_placa.php">
        <input type="hidden" name="id_muestra" value="<?= (int)$id_muestra ?>">
        <input type="hidden" name="id_analisis" value="<?= (int)$id_analisis ?>">
        <input type="hidden" name="id_protocolo" value="<?= (int)$id_protocolo ?>">
        <input type="hidden" name="id_resultado" value="<?= (int)($id_resultado_actual ?? 0) ?>">

        <div class="grid">
            <div class="campo">
                <label>No. lote antígeno / antisuero</label>
                <input type="text" name="lote_antigeno_antisuero" value="<?= h($datos_guardados['lote_antigeno_antisuero'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>
            <div class="campo">
                <label>Fecha</label>
                <input type="date" name="fecha" value="<?= h($datos_guardados['fecha'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>
            <div class="campo">
                <label>Responsable</label>
                <input type="text" name="responsable" value="<?= h($datos_guardados['responsable'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>
            <div class="campo">
                <label>Supervisor</label>
                <input type="text" name="supervisor" value="<?= h($datos_guardados['supervisor'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>
        </div>

        <div class="tabla-wrap">
            <table>
                <thead>
                    <tr>
                        <th colspan="9">RESULTADOS</th>
                    </tr>
                    <tr>
                        <th rowspan="2"># de suero</th>
                        <th colspan="2">MG</th>
                        <th colspan="2">MS</th>
                        <th colspan="2">Salmonella</th>
                        <th colspan="2">Otra: <input type="text" name="otra_nombre" value="<?= h($datos_guardados['otra_nombre'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?> style="width:120px;border:none;border-bottom:1px solid #666;background:transparent;"></th>
                    </tr>
                    <tr>
                        <th class="vertical">POSITIVO</th>
                        <th class="vertical">NEGATIVO</th>
                        <th class="vertical">POSITIVO</th>
                        <th class="vertical">NEGATIVO</th>
                        <th class="vertical">POSITIVO</th>
                        <th class="vertical">NEGATIVO</th>
                        <th class="vertical">POSITIVO</th>
                        <th class="vertical">NEGATIVO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 1; $i <= $cantidad_muestra; $i++): ?>
                        <tr>
                            <td><?= $i ?></td>

                            <?php foreach (['mg','ms','salmonella','otra'] as $campo): ?>
                                <td class="radio-cell">
                                    <label>
                                        <input type="radio" name="filas[<?= $i ?>][<?= $campo ?>]" value="positivo"
                                            <?= ($lineas[$i][$campo] ?? '') === 'positivo' ? 'checked' : '' ?>
                                            <?= $soloLectura ? 'disabled' : '' ?>>
                                    </label>
                                </td>
                                <td class="radio-cell">
                                    <label>
                                        <input type="radio" name="filas[<?= $i ?>][<?= $campo ?>]" value="negativo"
                                            <?= ($lineas[$i][$campo] ?? '') === 'negativo' ? 'checked' : '' ?>
                                            <?= $soloLectura ? 'disabled' : '' ?>>
                                    </label>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div class="resumen">
            <div class="resumen-box"><strong>MG</strong><br>Positivos: <span id="res-mg-pos"><?= contarResultado($lineas, 'mg', 'positivo') ?></span><br>Negativos: <span id="res-mg-neg"><?= contarResultado($lineas, 'mg', 'negativo') ?></span></div>
            <div class="resumen-box"><strong>MS</strong><br>Positivos: <span id="res-ms-pos"><?= contarResultado($lineas, 'ms', 'positivo') ?></span><br>Negativos: <span id="res-ms-neg"><?= contarResultado($lineas, 'ms', 'negativo') ?></span></div>
            <div class="resumen-box"><strong>Salmonella</strong><br>Positivos: <span id="res-salmonella-pos"><?= contarResultado($lineas, 'salmonella', 'positivo') ?></span><br>Negativos: <span id="res-salmonella-neg"><?= contarResultado($lineas, 'salmonella', 'negativo') ?></span></div>
            <div class="resumen-box"><strong>Otra</strong><br>Positivos: <span id="res-otra-pos"><?= contarResultado($lineas, 'otra', 'positivo') ?></span><br>Negativos: <span id="res-otra-neg"><?= contarResultado($lineas, 'otra', 'negativo') ?></span></div>
        </div>

        <div class="acciones-finales">
            <?php if ($soloLectura): ?>
                <div class="bloque-lectura" style="margin:0;">
                    Este resultado ya fue emitido. Para modificarlo, debe crear una corrección.
                </div>
            <?php else: ?>
                <button type="submit" class="btn-guardar">Guardar</button>
            <?php endif; ?>
            <a href="gestion_protocolos.php?id=<?= (int)$id_protocolo ?>&tab=tab_resultados" class="btn-regresar">Regresar</a>
        </div>
    </form>
</div>
</body>
</html>
