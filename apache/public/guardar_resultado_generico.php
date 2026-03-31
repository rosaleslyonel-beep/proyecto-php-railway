<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['usuario'])) {
    die("Usuario no autenticado.");
}

$id_muestra = $_GET['id_muestra'] ?? null;
$id_analisis = $_GET['id_analisis'] ?? null;
$usuario = $_SESSION['usuario']['id_usuario'];

if (!$id_muestra || !$id_analisis) {
    die("Datos incompletos.");
}

$stmt = $conexion->prepare("SELECT id_protocolo FROM muestras WHERE id_muestra = ?");
$stmt->execute([$id_muestra]);
$id_protocolo = $stmt->fetchColumn();

$stmt = $conexion->prepare("SELECT * FROM resultados_analisis WHERE id_muestra = ? AND id_analisis = ?");
$stmt->execute([$id_muestra, $id_analisis]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

$id_resultado_actual = $resultado['id_resultado'] ?? null;
if ($id_resultado_actual) {
    $stmt = $conexion->prepare("SELECT resultados_incluidos_json FROM protocolo_emisiones_resultados WHERE id_protocolo = ?");
    $stmt->execute([$id_protocolo]);
    $emisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($emisiones as $emision) {
        $ids = json_decode($emision['resultados_incluidos_json'] ?? '[]', true);
        if (is_array($ids) && in_array((int)$id_resultado_actual, array_map('intval', $ids), true)) {
            header("Location: resultado_analisis.php?id_protocolo=$id_protocolo&id_muestra=$id_muestra&id_analisis=$id_analisis&error=" . urlencode("Este resultado ya fue emitido y no puede modificarse directamente. Debe generar una corrección."));
            exit;
        }
    }
}

$observaciones = $_POST['observaciones'] ?? '';
$datos = [];

if ($resultado) {
    $datos_prev = json_decode($resultado['datos_json'] ?? '{}', true);
    if (!empty($datos_prev['archivo'])) {
        $datos['archivo'] = $datos_prev['archivo'];
    }
}

if (!empty($_FILES['archivo']['name'])) {
    if ($_FILES['archivo']['error'] === UPLOAD_ERR_INI_SIZE) {
        die("El archivo excede el tamaño máximo permitido por el servidor.");
    }
    if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        die("Error al subir archivo. Código: " . $_FILES['archivo']['error']);
    }

    $directorio = __DIR__ . "/uploads/resultados/";
    if (!is_dir($directorio)) {
        if (!mkdir($directorio, 0777, true)) {
            die("No se pudo crear el directorio de destino.");
        }
    }
    if (!is_writable($directorio)) {
        die("El directorio no tiene permisos de escritura.");
    }

    $nombre_original = $_FILES["archivo"]["name"];
    $nombre_limpio = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre_original);
    $archivo_nombre = time() . "_" . $nombre_limpio;
    $ruta = $directorio . $archivo_nombre;

    if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $ruta)) {
        $datos['archivo'] = $archivo_nombre;
    } else {
        die("No se pudo mover el archivo al destino final.");
    }
}

if ($resultado) {
    $stmt = $conexion->prepare("
        UPDATE resultados_analisis
        SET datos_json = :datos, observaciones = :observaciones, updated_by = :usuario, updated_date = NOW()
        WHERE id_resultado = :id
    ");
    $stmt->execute([
        ':datos' => json_encode($datos),
        ':observaciones' => $observaciones,
        ':usuario' => $usuario,
        ':id' => $resultado['id_resultado']
    ]);
} else {
    $stmt = $conexion->prepare("
        INSERT INTO resultados_analisis (id_muestra, id_analisis, datos_json, observaciones, created_by)
        VALUES (:id_muestra, :id_analisis, :datos, :observaciones, :usuario)
    ");
    $stmt->execute([
        ':id_muestra' => $id_muestra,
        ':id_analisis' => $id_analisis,
        ':datos' => json_encode($datos),
        ':observaciones' => $observaciones,
        ':usuario' => $usuario
    ]);
}

header("Location: gestion_protocolos.php?id=$id_protocolo&tab=tab_resultados");
exit;
?>