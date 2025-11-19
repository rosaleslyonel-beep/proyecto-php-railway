<?php
require_once "../config/helpers.php";
session_start();

$id_muestra = $_POST['id_muestra'] ?? null;
$analisis_ids = $_POST['analisis_ids'] ?? [];
$usuario_id = $_SESSION['usuario']['id_usuario'] ?? null;

if (!$id_muestra) {
    echo "Error: Falta ID de muestra.";
    exit;
}

try {
    // Siempre eliminar los análisis existentes primero
    $stmt = $conexion->prepare("DELETE FROM muestra_analisis WHERE id_muestra = ?");
    $stmt->execute([$id_muestra]);

    // Solo insertar si hay nuevos
    if (!empty($analisis_ids)) {
        $stmt_insert = $conexion->prepare("
            INSERT INTO muestra_analisis (id_muestra, id_analisis, precio_unitario, created_by, created_date)
            SELECT ?, id_analisis, precio, ?, NOW()
            FROM analisis_laboratorio
            WHERE id_analisis = ?
        ");

        foreach ($analisis_ids as $id_analisis) {
            $stmt_insert->execute([$id_muestra, $usuario_id, $id_analisis]);
        }
    }

} catch (PDOException $e) {
    echo "Error al guardar análisis: " . $e->getMessage();
}
