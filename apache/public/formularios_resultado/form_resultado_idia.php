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

$placas_guardadas = $datos_guardados['placas'] ?? [];
if (!is_array($placas_guardadas)) $placas_guardadas = [];
$placas_normalizadas = [];
for ($i=0; $i<$cantidad_muestra; $i++) {
    $placas_normalizadas[] = $placas_guardadas[$i] ?? 'negativo';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Resultado IDIA</title>
<style>
.idia-wrap{font-family:Arial,sans-serif;max-width:1100px;margin:0 auto;background:#fff;border:1px solid #ddd;border-radius:10px;padding:20px}
.top-actions{margin-bottom:15px}
.btn-regresar{display:inline-block;text-decoration:none;padding:10px 14px;background:#6c757d;color:#fff;border-radius:6px;font-weight:bold}
.bloque-lectura{background:#e2e3e5;border:1px solid #c6c7c8;color:#41464b;padding:12px;margin:15px 0;border-radius:6px}
.titulo{margin:0 0 15px 0;text-align:center}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(260px,1fr));gap:14px 18px;margin-bottom:18px}
.campo{display:flex;flex-direction:column;gap:6px}
.campo label{font-weight:bold;color:#333}
.campo input,.campo textarea{width:100%;border:1px solid #ccc;border-radius:6px;padding:10px;font-size:15px;box-sizing:border-box}
.campo-full{grid-column:1 / -1}
.circulos-wrap{margin-top:18px}
.circulos-info{display:flex;flex-wrap:wrap;gap:18px;align-items:center;margin-bottom:12px}
.badge-info{display:inline-block;background:#f5f5f5;border:1px solid #ddd;border-radius:999px;padding:8px 12px;font-weight:bold}
.circulos-grid{display:flex;flex-wrap:wrap;gap:14px}
.circulo-item{display:flex;flex-direction:column;align-items:center;gap:8px;width:82px}
.circulo{width:54px;height:54px;border-radius:50%;border:2px solid #666;display:flex;align-items:center;justify-content:center;font-weight:bold;cursor:pointer;user-select:none;background:#e9ecef;color:#333}
.circulo.positivo{background:#e57373;color:#fff;border-color:#c62828}
.circulo.negativo{background:#81c784;color:#fff;border-color:#2e7d32}
.circulo-item small{color:#555}
.resumen{margin-top:14px;display:flex;gap:14px;flex-wrap:wrap}
.resumen-box{background:#f8f9fa;border:1px solid #ddd;border-radius:8px;padding:10px 12px;min-width:170px}
.acciones-finales{margin-top:18px;display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.btn-guardar{padding:10px 16px;border:none;background:#198754;color:#fff;font-weight:bold;border-radius:6px;cursor:pointer}
@media (max-width: 800px){.form-grid{grid-template-columns:1fr}}
</style>
<script>
const soloLectura = <?= $soloLectura ? 'true' : 'false' ?>;
function toggleResultado(btn){
    if(soloLectura) return;
    if(btn.classList.contains('positivo')){
        btn.classList.remove('positivo'); btn.classList.add('negativo'); btn.dataset.valor='negativo'; btn.innerText='−';
    } else {
        btn.classList.remove('negativo'); btn.classList.add('positivo'); btn.dataset.valor='positivo'; btn.innerText='+';
    }
    recalcularResumenIDIA();
}
function prepararEnvio(){
    const placas=document.querySelectorAll('.circulo');
    const resultados=Array.from(placas).map(p=>p.dataset.valor);
    document.getElementById('placas_data').value=JSON.stringify(resultados);
}
function recalcularResumenIDIA(){
    const placas=document.querySelectorAll('.circulo');
    let positivos=0, negativos=0;
    placas.forEach(p=>{ if(p.dataset.valor==='positivo') positivos++; else negativos++; });
    document.getElementById('total-positivos').textContent=positivos;
    document.getElementById('total-negativos').textContent=negativos;
}
function regresar(){ window.location.href='gestion_protocolos.php?tab=tab_resultados&id=' + <?= json_encode($id_protocolo) ?>; }
window.onload=function(){ recalcularResumenIDIA(); }
</script>
</head>
<body>
<div class="idia-wrap">
    <div class="top-actions">
        <a href="gestion_protocolos.php?id=<?= (int)$id_protocolo ?>&tab=tab_resultados" class="btn-regresar">← Regresar a Resultados</a>
    </div>

    <h2 class="titulo">Resultado IDIA</h2>

    <?php if ($soloLectura): ?>
        <div class="bloque-lectura">
            Este resultado ya fue emitido. Solo está disponible para consulta.
            Para modificarlo, debe crear una corrección.
        </div>
    <?php endif; ?>

    <form method="POST" action="guardar_resultado_idia.php" onsubmit="prepararEnvio()">
        <input type="hidden" name="id_resultado" value="<?= htmlspecialchars($id_resultado_actual ?? '') ?>">
        <input type="hidden" name="id_muestra" value="<?= htmlspecialchars($id_muestra) ?>">
        <input type="hidden" name="id_analisis" value="<?= htmlspecialchars($id_analisis) ?>">
        <input type="hidden" name="id_protocolo" value="<?= htmlspecialchars($id_protocolo) ?>">
        <input type="hidden" name="placas" id="placas_data">

        <div class="form-grid">
            <div class="campo">
                <label>No. de Lote del antígeno / Antisuero</label>
                <input type="text" name="lote_antigeno" value="<?= h($datos_guardados['lote_antigeno'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Lote de elaboración del Agar</label>
                <input type="text" name="lote_agar" value="<?= h($datos_guardados['lote_agar'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Fecha de elaboración</label>
                <input type="date" name="fecha_elaboracion" value="<?= h($datos_guardados['fecha_elaboracion'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Procesada por</label>
                <input type="text" name="procesada_por" value="<?= h($datos_guardados['procesada_por'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Prueba para</label>
                <input type="text" name="prueba_para" value="<?= h($datos_guardados['prueba_para'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Placa No</label>
                <input type="text" name="placa_no" value="<?= h($datos_guardados['placa_no'] ?? $cantidad_muestra) ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Fecha de Lectura</label>
                <input type="date" name="fecha_lectura" value="<?= h($datos_guardados['fecha_lectura'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo">
                <label>Realizada por</label>
                <input type="text" name="realizada_por" value="<?= h($datos_guardados['realizada_por'] ?? '') ?>" <?= $soloLectura ? 'readonly' : '' ?>>
            </div>

            <div class="campo campo-full">
                <label>Observaciones</label>
                <textarea name="observaciones" rows="4" <?= $soloLectura ? 'readonly' : '' ?>><?= h($resultado['observaciones'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="circulos-wrap">
            <div class="circulos-info">
                <div class="badge-info">Cantidad de unidades en muestra: <?= (int)$cantidad_muestra ?></div>
                <div class="badge-info">Muestra: <?= h($muestra['tipo_muestra']) ?></div>
            </div>

            <div class="circulos-grid">
                <?php foreach ($placas_normalizadas as $idx => $estado): ?>
                    <?php $estado = ($estado === 'positivo') ? 'positivo' : 'negativo'; $simbolo = ($estado === 'positivo') ? '+' : '−'; ?>
                    <div class="circulo-item">
                        <div class="circulo <?= $estado ?>" data-valor="<?= $estado ?>" onclick="toggleResultado(this)"><?= $simbolo ?></div>
                        <small>Muestra <?= $idx + 1 ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="resumen">
                <div class="resumen-box"><strong>Total positivos:</strong> <span id="total-positivos">0</span></div>
                <div class="resumen-box"><strong>Total negativos:</strong> <span id="total-negativos">0</span></div>
            </div>
        </div>

        <div class="acciones-finales">
            <?php if ($soloLectura): ?>
                <div class="bloque-lectura" style="margin:0;">
                    Este resultado ya fue emitido. Para modificarlo, debe crear una corrección.
                </div>
            <?php else: ?>
                <button type="submit" class="btn-guardar">Guardar</button>
            <?php endif; ?>
            <button type="button" class="btn-regresar" onclick="regresar()">Regresar</button>
        </div>
    </form>
</div>
</body>
</html>
