<?php
require_once "config/database.php";
 
if (!isset($_POST['placas']) || !isset($_GET['id_muestra']) || !isset($_GET['id_analisis'])) {
    echo "<p>Error: Faltan datos del formulario.</p>";
    exit();
}  
$id_muestra = (int) $_GET['id_muestra'];
$id_analisis = (int) $_GET['id_analisis'];
$usuario = $_SESSION['usuario']['id_usuario'] ?? 1;

// Serializar los resultados de la placa
$placa = $_POST['placas'];
$placa_json = json_encode($placa);

$observaciones = $_POST['observaciones'] ?? null;
$lote_antigeno = $_POST['lote_antigeno'] ?? null;
$fecha_elaboracion = $_POST['fecha_elaboracion'] ?? null;
$prueba_para = $_POST['prueba_para'] ?? null;
$hora_inicio = $_POST['hora_inicio'] ?? null;
$hora_fin = $_POST['hora_fin'] ?? null;
$responsable = $_POST['responsable'] ?? null;
$lote_cp = $_POST['lote_cp'] ?? null;
$resultado_cp = $_POST['resultado_cp'] ?? null;
$lote_cn = $_POST['lote_cn'] ?? null;
$resultado_cn = $_POST['resultado_cn'] ?? null;

// Verificar si ya existe resultado
$stmt = $conexion->prepare("SELECT id_resultado FROM resultados_analisis WHERE id_muestra = ? AND id_analisis = ?");
$stmt->execute([$id_muestra, $id_analisis]);
$existe = $stmt->fetchColumn();

// Verificar si ya existe resultado
$stmt = $conexion->prepare("SELECT id_protocolo FROM muestras WHERE id_muestra =? ");
$stmt->execute([$id_muestra]);
$id_protocolo = $stmt->fetchColumn();



if ($existe) {
    // Actualizar
    $stmt = $conexion->prepare("UPDATE resultados_analisis SET
        datos_json = :datos,
        observaciones = :observaciones,
        lote_antigeno = :lote_antigeno,
        fecha_elaboracion = :fecha_elaboracion,
        prueba_para = :prueba_para,
        hora_inicio = :hora_inicio,
        hora_fin = :hora_fin,
        responsable = :responsable,
        lote_cp = :lote_cp,
        resultado_cp = :resultado_cp,
        lote_cn = :lote_cn,
        resultado_cn = :resultado_cn,
        updated_by = :usuario,
        updated_date = NOW()
        WHERE id_resultado = :id");
    $stmt->execute([
        ':datos' => $placa_json,
        ':observaciones' => $observaciones,
        ':lote_antigeno' => $lote_antigeno,
        ':fecha_elaboracion' => $fecha_elaboracion,
        ':prueba_para' => $prueba_para,
        ':hora_inicio' => $hora_inicio,
        ':hora_fin' => $hora_fin,
        ':responsable' => $responsable,
        ':lote_cp' => $lote_cp,
        ':resultado_cp' => $resultado_cp,
        ':lote_cn' => $lote_cn,
        ':resultado_cn' => $resultado_cn,
        ':usuario' => $usuario,
        ':id' => $existe
    ]);
} else {
    // Insertar
    $stmt = $conexion->prepare("INSERT INTO resultados_analisis (
        id_muestra, id_analisis, datos_json, observaciones,
        lote_antigeno, fecha_elaboracion, prueba_para, hora_inicio,
        hora_fin, responsable, lote_cp, resultado_cp, lote_cn, resultado_cn,
        created_by
    ) VALUES (
        :id_muestra, :id_analisis, :datos, :observaciones,
        :lote_antigeno, :fecha_elaboracion, :prueba_para, :hora_inicio,
        :hora_fin, :responsable, :lote_cp, :resultado_cp, :lote_cn, :resultado_cn,
        :usuario)");
    $stmt->execute([
        ':id_muestra' => $id_muestra,
        ':id_analisis' => $id_analisis,
        ':datos' => $placa_json,
        ':observaciones' => $observaciones,
        ':lote_antigeno' => $lote_antigeno,
        ':fecha_elaboracion' => $fecha_elaboracion,
        ':prueba_para' => $prueba_para,
        ':hora_inicio' => $hora_inicio,
        ':hora_fin' => $hora_fin,
        ':responsable' => $responsable,
        ':lote_cp' => $lote_cp,
        ':resultado_cp' => $resultado_cp,
        ':lote_cn' => $lote_cn,
        ':resultado_cn' => $resultado_cn,
        ':usuario' => $usuario
    ]);
}

// Redirigir de vuelta
header("Location: gestion_protocolos.php?id=$id_protocolo&tab=tab_resultados");
exit();
?>
