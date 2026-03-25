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

// Obtener protocolo
$stmt = $conexion->prepare("SELECT id_protocolo FROM muestras WHERE id_muestra = ?");
$stmt->execute([$id_muestra]);
$id_protocolo = $stmt->fetchColumn();

// Obtener resultado existente
$stmt = $conexion->prepare("SELECT * FROM resultados_analisis WHERE id_muestra = ? AND id_analisis = ?");
$stmt->execute([$id_muestra, $id_analisis]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

$observaciones = $_POST['observaciones'] ?? '';
$datos = [];


// =====================
// 📎 MANEJO DE ARCHIVO
// =====================
$archivo_nombre = null;

if (!empty($_FILES['archivo']['name'])) {

    $directorio = "uploads/resultados/";

    if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $archivo_nombre = time() . "_" . basename($_FILES["archivo"]["name"]);
    $ruta = $directorio . $archivo_nombre;
    if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $ruta)) {
        echo "Guardado en: " . realpath($ruta);
        exit; 
    } else {
        echo "No se pudo guardar.";
        exit;
    }
    $datos['archivo'] = $archivo_nombre;
} else {
    // Mantener archivo anterior si existe
    if ($resultado) {
        $datos_prev = json_decode($resultado['datos_json'], true);
        if (!empty($datos_prev['archivo'])) {
            $datos['archivo'] = $datos_prev['archivo'];
        }
    }
}


// =====================
// GUARDAR
// =====================

if ($resultado) {
    $stmt = $conexion->prepare("
        UPDATE resultados_analisis
        SET datos_json = :datos,
            observaciones = :observaciones,
            updated_by = :usuario,
            updated_date = NOW()
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
        INSERT INTO resultados_analisis
        (id_muestra, id_analisis, datos_json, observaciones, created_by)
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

// Redirigir
header("Location: gestion_protocolos.php?id=$id_protocolo&tab=tab_resultados");
exit;