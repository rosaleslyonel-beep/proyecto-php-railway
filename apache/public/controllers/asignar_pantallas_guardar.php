<?php
require_once "../config/database.php";
require_once "../config/helpers.php";
session_start();

if (!verificarPermiso(5, 'modificar')) {
    header("Location: dashboard.php"); // o muestra mensaje
    exit();
}

$id_rol = $_POST['id_rol'];
$permisos = $_POST['permisos'] ?? [];

try {
    $conexion->beginTransaction();

    // Limpiar permisos anteriores
    $conexion->prepare("DELETE FROM permisos WHERE id_rol = :id")->execute([':id' => $id_rol]);

    // Insertar nuevos
    $stmt = $conexion->prepare("
        INSERT INTO permisos (id_rol, id_pantalla, consultar, agregar, modificar, eliminar) 
        VALUES (:id_rol, :id_pantalla, :consultar, :agregar, :modificar, :eliminar)
    ");

    foreach ($permisos as $id_pantalla => $acciones) {
        $stmt->execute([
            ':id_rol' => $id_rol,
            ':id_pantalla' => $id_pantalla,
            ':consultar' => isset($acciones['consultar']) ? 1 : 0,
            ':agregar' => isset($acciones['agregar']) ? 1 : 0,
            ':modificar' => isset($acciones['modificar']) ? 1 : 0,
            ':eliminar' => isset($acciones['eliminar']) ? 1 : 0
        ]);
    }

    $conexion->commit();
    header("Location: ../gestion_roles.php?id=$id_rol");
    exit();

} catch (PDOException $e) {
    $conexion->rollBack();
    echo "<h3>❌ Error al guardar permisos:</h3><pre>" . $e->getMessage() . "</pre>";
    exit();
}
