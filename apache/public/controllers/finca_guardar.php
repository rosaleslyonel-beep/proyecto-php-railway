<?php
require_once "../config/database.php";
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id = isset($_POST['id_finca']) && is_numeric($_POST['id_finca']) ? (int)$_POST['id_finca'] : null;
$id_cliente = $_POST['id_cliente'];
$nombre = trim($_POST['nombre_finca']);
$ubicacion = trim($_POST['ubicacion']);
$fecha = date("Y-m-d H:i:s");
$id_usuario = $_SESSION['usuario']['id_usuario'] ?? 1; // fallback

try {
    if ($id) {
        // Actualizar finca existente
        $stmt = $conexion->prepare("
            UPDATE fincas SET 
                id_cliente = :id_cliente,
                nombre_finca = :nombre,
                ubicacion = :ubicacion,
                updated_by = :usuario,
                updated_date = :fecha
            WHERE id_finca = :id
        ");
        $stmt->execute([
            ':id_cliente' => $id_cliente,
            ':nombre' => $nombre,
            ':ubicacion' => $ubicacion,
            ':usuario' => $id_usuario,
            ':fecha' => $fecha,
            ':id' => $id
        ]);
    } else {
        // Insertar nueva finca
        $stmt = $conexion->prepare("
            INSERT INTO fincas (id_cliente, nombre_finca, ubicacion, created_by, created_date)
            VALUES (:id_cliente, :nombre, :ubicacion, :usuario, :fecha)
        ");
        $stmt->execute([
            ':id_cliente' => $id_cliente,
            ':nombre' => $nombre,
            ':ubicacion' => $ubicacion,
            ':usuario' => $id_usuario,
            ':fecha' => $fecha
        ]);
    }
    $redirect = ($_SESSION['usuario']['rol_nombre'] === 'admin')
    ? "../gestion_fincas.php?id_cliente={$id_cliente}"
    : "../gestion_fincas.php?id_cliente={$id_cliente}";
    //header("Location: ../gestion_fincas.php");
 
    header("Location: $redirect");

    exit();

} catch (PDOException $e) {
    echo "<h3>❌ Error al guardar finca:</h3><pre>" . $e->getMessage() . "</pre>";
    exit();
}
