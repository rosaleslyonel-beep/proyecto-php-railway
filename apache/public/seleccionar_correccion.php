<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$id_protocolo = isset($_GET['id_protocolo']) ? (int)$_GET['id_protocolo'] : 0;
if ($id_protocolo <= 0) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $conexion->prepare("SELECT * FROM protocolos WHERE id_protocolo = ?");
$stmt->execute([$id_protocolo]);
$protocolo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$protocolo) {
    die("No se encontró el protocolo.");
}

$stmt = $conexion->prepare("
    SELECT
        m.id_muestra,
        m.tipo_muestra,
        a.id_analisis,
        a.nombre_estudio,
        ra.id_resultado,
        COALESCE(ma.estado_resultado, 'ACTIVO') AS estado_resultado
    FROM muestras m
    JOIN muestra_analisis ma ON ma.id_muestra = m.id_muestra
    JOIN analisis_laboratorio a ON a.id_analisis = ma.id_analisis
    JOIN resultados_analisis ra ON ra.id_muestra = ma.id_muestra AND ra.id_analisis = ma.id_analisis
    WHERE m.id_protocolo = ?
      AND COALESCE(ma.estado_resultado, 'ACTIVO') = 'ACTIVO'
    ORDER BY m.id_muestra, a.nombre_estudio
");
$stmt->execute([$id_protocolo]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$agrupado = [];
foreach ($filas as $fila) {
    $agrupado[$fila['id_muestra']]['tipo_muestra'] = $fila['tipo_muestra'];
    $agrupado[$fila['id_muestra']]['analisis'][] = $fila;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seleccionar corrección</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background:#f7f7f7; color:#222; }
        .contenedor { max-width: 980px; margin:0 auto; background:#fff; border:1px solid #ddd; border-radius:10px; padding:24px; }
        .acciones { margin-bottom:16px; display:flex; gap:10px; flex-wrap:wrap; }
        .btn { display:inline-block; padding:10px 14px; border-radius:6px; text-decoration:none; color:#fff; background:#6c757d; font-weight:bold; border:none; cursor:pointer; }
        .btn.pri { background:#fd7e14; }
        .bloque { border:1px solid #ddd; border-radius:8px; margin-bottom:18px; overflow:hidden; }
        .bloque h3 { margin:0; padding:12px 14px; background:#f1f3f5; }
        .item { display:flex; gap:12px; padding:12px 14px; border-top:1px solid #eee; align-items:flex-start; }
        .help { background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding:12px; border-radius:6px; margin-bottom:16px; }
    </style>
</head>
<body>
<div class="contenedor">
    <div class="acciones">
        <a class="btn" href="gestion_protocolos.php?id=<?= (int)$id_protocolo ?>&tab=tab_resultados">← Regresar</a>
    </div>

    <h2>Crear corrección</h2>
    <p><strong>Protocolo:</strong> <?= htmlspecialchars($protocolo['correlativo'] ?: ('ID ' . $protocolo['id_protocolo'])) ?></p>

    <div class="help">
        Seleccione únicamente los análisis ya ingresados que deban enviarse a un nuevo protocolo de corrección.
    </div>

    <?php if (!$filas): ?>
        <p>No hay análisis con resultado disponibles para corrección.</p>
    <?php else: ?>
        <form method="post" action="crear_correccion.php">
            <input type="hidden" name="id_protocolo" value="<?= (int)$id_protocolo ?>">
            <?php foreach ($agrupado as $idMuestra => $bloque): ?>
                <div class="bloque">
                    <h3>Muestra <?= (int)$idMuestra ?> - <?= htmlspecialchars($bloque['tipo_muestra']) ?></h3>
                    <?php foreach ($bloque['analisis'] as $a): ?>
                        <label class="item">
                            <input type="checkbox" name="selecciones[]" value="<?= (int)$a['id_muestra'] ?>|<?= (int)$a['id_analisis'] ?>">
                            <div>
                                <strong><?= htmlspecialchars($a['nombre_estudio']) ?></strong><br>
                                <small>ID análisis: <?= (int)$a['id_analisis'] ?> · Resultado registrado: <?= (int)$a['id_resultado'] ?></small>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn pri" onclick="return confirm('¿Desea crear el protocolo de corrección con los análisis seleccionados?');">
                Crear protocolo de corrección
            </button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
