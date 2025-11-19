<?php
require_once "../config/database.php";
require_once "../config/helpers.php";
session_start();

if (!verificarPermiso(9, 'modificar')) {
    header("Location: dashboard.php"); // o muestra mensaje
    exit();
}

$nombre_pantalla = trim($_POST['nombre_pantalla']);
 
$id_usuario = $_SESSION['usuario']['id_usuario'];
$fecha = date('Y-m-d H:i:s');
$id = isset($_POST['id_pantalla']) && is_numeric($_POST['id_pantalla']) ? (int)$_POST['id_pantalla'] : null;

try {
    // Verificar duplicados por nombre_pantalla o ruta
    $query = "SELECT id_pantalla FROM pantallas WHERE (nombre_pantalla = :nombre_pantalla  )";
    $params = [':nombre_pantalla' => $nombre_pantalla ];
    if ($id) {
        $query .= " AND id_pantalla <> :id_actual";
        $params[':id_actual'] = $id;
    }

    $stmt = $conexion->prepare($query);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo "<h3>⚠️ Ya existe una pantalla con ese nombre .</h3>";
        echo "<a href='../gestion_pantallas.php?id=$id&search=" . urlencode($_GET['search'] ?? '') . "'>← Volver</a>";
        exit();
    }

    if ($id) {
        // Actualizar
        $stmt = $conexion->prepare("
            UPDATE pantallas
            SET nombre_pantalla = :nombre_pantalla,
                 
                updated_by = :usuario,
                updated_date = :fecha
            WHERE id_pantalla = :id
        ");
        $stmt->execute([
            ':nombre_pantalla' => $nombre_pantalla,
           
            ':usuario' => $id_usuario,
            ':fecha' => $fecha,
            ':id' => $id
        ]);
    } else {
        // Insertar
        $stmt = $conexion->prepare("
            INSERT INTO pantallas (nombre_pantalla,   created_by, created_date)
            VALUES (:nombre_pantalla,   :usuario, :fecha)
        ");
        $stmt->execute([
            ':nombre_pantalla' => $nombre_pantalla,
           
            ':usuario' => $id_usuario,
            ':fecha' => $fecha
        ]);
    }

    header("Location: ../gestion_pantallas.php");
    exit();

} catch (PDOException $e) {
    echo "<h3>❌ Error al guardar pantalla:</h3><pre>" . $e->getMessage() . "</pre>";
    exit();
}
