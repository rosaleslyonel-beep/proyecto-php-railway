<?php
require_once "database.php";

function verificarPermiso($id_pantalla, $accion) {
    global $conexion;
    if (!isset($_SESSION['usuario'])) return false;

    $rol = $_SESSION['usuario']['rol'];

    // Guardamos los permisos en sesión si no existen aún (cache básica)
    if (!isset($_SESSION['permisos'])) {

        $stmt = $conexion->prepare("
            SELECT id_pantalla, consultar, agregar, modificar, eliminar
            FROM permisos
            WHERE id_rol = :rol
        ");
        $stmt->execute([':rol' => $rol]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $_SESSION['permisos'] = [];
        foreach ($resultados as $perm) {
            $_SESSION['permisos'][$perm['id_pantalla']] = [
                'consultar' => (bool)$perm['consultar'],
                'agregar' => (bool)$perm['agregar'],
                'modificar' => (bool)$perm['modificar'],
                'eliminar' => (bool)$perm['eliminar']
            ];
        }
    
    }
    return $_SESSION['permisos'][$id_pantalla][$accion] ?? false;
}

?>