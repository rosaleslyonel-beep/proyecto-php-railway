<?php
require_once "../config/database.php";
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol_nombre'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$nombre = trim($_POST['nombre_tipo']);
$prefijo = strtoupper(trim($_POST['prefijo']));
$id_usuario = $_SESSION['usuario']['id_usuario'];
$fecha = date('Y-m-d H:i:s');
$id = isset($_POST['id_tipo_protocolo']) && is_numeric($_POST['id_tipo_protocolo']) ? (int)$_POST['id_tipo_protocolo'] : null;

try {
    // Verificar duplicados (nombre o prefijo en otro ID)
    $query = "SELECT id_tipo_protocolo FROM tipos_protocolo WHERE (nombre_tipo = :nombre OR prefijo = :prefijo)";
    $params = [':nombre' => $nombre, ':prefijo' => $prefijo];

    if ($id) {
        $query .= " AND id_tipo_protocolo <> :id_actual";
        $params[':id_actual'] = $id;
    }

    $stmt = $conexion->prepare($query);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo "<h3>⚠️ Ya existe un tipo de protocolo con ese nombre o prefijo.</h3>";
        echo "<a href='../gestion_tipos_protocolo.php?id=$id&search=" . urlencode($_GET['search'] ?? '') . "'>← Volver</a>";
        exit();
    }

    if ($id) {
        // Actualizar
        $stmt = $conexion->prepare("
            UPDATE tipos_protocolo
            SET nombre_tipo = :nombre,
                prefijo = :prefijo,
                updated_by = :usuario,
                updated_date = :fecha
            WHERE id_tipo_protocolo = :id
        ");
        $stmt->execute([
            ':nombre' => $nombre,
            ':prefijo' => $prefijo,
            ':usuario' => $id_usuario,
            ':fecha' => $fecha,
            ':id' => $id
        ]);
    } else {
        // Insertar
        $stmt = $conexion->prepare("
            INSERT INTO tipos_protocolo (nombre_tipo, prefijo, created_by, created_date)
            VALUES (:nombre, :prefijo, :usuario, :fecha)
        ");
        $stmt->execute([
            ':nombre' => $nombre,
            ':prefijo' => $prefijo,
            ':usuario' => $id_usuario,
            ':fecha' => $fecha
        ]);
    }

    header("Location: ../gestion_tipos_protocolo.php");
    exit();

} catch (PDOException $e) {
    echo "<h3>❌ Error al guardar tipo de protocolo:</h3><pre>" . $e->getMessage() . "</pre>";
    exit();
}
