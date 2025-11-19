<?php
require_once "../config/helpers.php";
session_start();

// Validar si el usuario tiene permiso de editar/crear/eliminar (opcional)
// if (!usuario_tiene_permiso('analisis_editar')) {
//     header("Location: ../gestion_analisis.php?error=sin_permiso");
//     exit;
// }

if (isset($_POST['eliminar']) && isset($_POST['id_analisis'])) {
    // Eliminar análisis
    $id_analisis = intval($_POST['id_analisis']);
    $stmt = $conexion->prepare("DELETE FROM analisis_laboratorio WHERE id_analisis = ?");
    $stmt->execute([$id_analisis]);
    echo "ok";
    exit;
}

$nombre_estudio = trim($_POST['nombre_estudio'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio = $_POST['precio'] ?? '';
$id_analisis = $_POST['id_analisis'] ?? '';
$tipo_formulario = $_POST['tipo_formulario'] ?? '';

if ($nombre_estudio === '' || $precio === '') {
    header("Location: ../gestion_analisis.php?error=campos_obligatorios");
    exit;
}

// Usuario que realiza la operación (ajusta según tu sistema)
$usuario_id = $_SESSION['usuario']['id_usuario'] ?? null;

try {
    if ($id_analisis) {
        // Modificar análisis existente
        $stmt = $conexion->prepare("UPDATE analisis_laboratorio
            SET nombre_estudio = ?, descripcion = ?, precio = ?, tipo_formulario = ?,updated_by = ?, updated_date = NOW()
            WHERE id_analisis = ?");
        $stmt->execute([
            $nombre_estudio,
            $descripcion,
            $precio,
            $tipo_formulario,
            $usuario_id,            
            $id_analisis
        ]);
        
    } else {
        // Crear nuevo análisis
        $stmt = $conexion->prepare("INSERT INTO analisis_laboratorio
            (nombre_estudio, descripcion, precio,tipo_formulario, created_by, created_date)
            VALUES (?, ?, ?,?, ?, NOW())");
        $stmt->execute([
            $nombre_estudio,
            $descripcion,
            $precio,
            $tipo_formulario,
            $usuario_id
        ]);
    }
    // Guardar roles asociados
    $stmt = $conexion->prepare("DELETE FROM analisis_roles WHERE id_analisis = ?");
    $stmt->execute([$id_analisis]);

    if (!empty($_POST['roles'])) {
        $stmtInsert = $conexion->prepare("INSERT INTO analisis_roles (id_analisis, id_rol, created_by) VALUES (?, ?, ?)");
        foreach ($_POST['roles'] as $id_rol) {
            $stmtInsert->execute([$id_analisis, $id_rol, $usuario_id]);
        }
    }
    header("Location: ../gestion_analisis.php?id=".$id_analisis);
    exit;
} catch (PDOException $e) {
    // Manejar errores
    header("Location: ../gestion_analisis.php?error=" . urlencode($e->getMessage()));
    exit;
}
