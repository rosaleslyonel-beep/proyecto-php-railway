<?php
//session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Asignación de permisos a roles
    if (isset($_POST["asignar_permiso"])) {
        $id_rol = $_POST["id_rol"];
        $pantalla = $_POST["pantalla"];
        $tipo_acceso = $_POST["tipo_acceso"];

        try {
            $stmt = $conexion->prepare("INSERT INTO permisos (nombre_pantalla, tipo_acceso, id_rol) 
                                        VALUES (:pantalla, :tipo_acceso, :id_rol)");
            $stmt->bindParam(":pantalla", $pantalla);
            $stmt->bindParam(":tipo_acceso", $tipo_acceso);
            $stmt->bindParam(":id_rol", $id_rol);
            $stmt->execute();
            header("Location: ../gestion_roles.php?success=1");
        } catch (PDOException $e) {
            header("Location: ../gestion_roles.php?error=1");
        }
        exit();
    }

    // Asignación de roles a usuarios
    if (isset($_POST["asignar_rol"])) {
        $id_usuario = $_POST["id_usuario"];
        $id_rol = $_POST["id_rol"];

        try {
            $stmt = $conexion->prepare("UPDATE usuarios SET id_rol = :id_rol WHERE id_usuario = :id_usuario");
            $stmt->bindParam(":id_rol", $id_rol);
            $stmt->bindParam(":id_usuario", $id_usuario);
            $stmt->execute();
            header("Location: ../gestion_roles.php?success=2");
        } catch (PDOException $e) {
            header("Location: ../gestion_roles.php?error=2");
        }
        exit();
    }
    // Agregar nueva pantalla
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_pantalla"])) {
    $nombre_pantalla = $_POST["nombre_pantalla"];
    $descripcion = $_POST["descripcion"];

    try {
        $stmt = $conexion->prepare("INSERT INTO pantallas (nombre_pantalla, descripcion) VALUES (:nombre, :descripcion)");
        $stmt->bindParam(":nombre", $nombre_pantalla);
        $stmt->bindParam(":descripcion", $descripcion);
        $stmt->execute();
        header("Location:  ../gestion_pantallas.php?success=pantalla_agregada");
    } catch (PDOException $e) {
        header("Location: ../gestion_pantallas.php?error=pantalla_error");
    }
    exit();
}




// Asignar pantalla a un rol
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["assign_pantalla"])) {
    $id_rol = $_POST["id_rol"];
    $id_pantalla = $_POST["id_pantalla"];
    $permiso = $_POST["permiso"];

    try {
        $stmt = $conexion->prepare("INSERT INTO permisos (id_rol, id_pantalla, tipo_acceso) 
                                    VALUES (:id_rol, :id_pantalla, :permiso)");
        $stmt->bindParam(":id_rol", $id_rol);
        $stmt->bindParam(":id_pantalla", $id_pantalla);
        $stmt->bindParam(":permiso", $permiso);
        $stmt->execute();
        header("Location: ../gestion_roles.php?success=asignado");
    } catch (PDOException $e) {
        header("Location: ../gestion_roles.php?error=asignar");
    }
    exit();
}



}
// Eliminar pantalla
if (isset($_GET["delete_pantalla"])) {
    $id_pantalla = $_GET["delete_pantalla"];

    try {
        $stmt = $conexion->prepare("DELETE FROM pantallas WHERE id_pantalla = :id");
        $stmt->bindParam(":id", $id_pantalla);
        $stmt->execute();
        header("Location: ../gestion_pantallas.php?success=pantalla_eliminada");
    } catch (PDOException $e) {
        header("Location: ../gestion_pantallas.php?error=pantalla_error");
    }
    exit();
}
// Eliminar asignación de pantalla a un rol
if (isset($_GET["delete_permiso"])) {
    $id_permiso = $_GET["delete_permiso"];
 
    try {
        $stmt = $conexion->prepare("DELETE FROM permisos WHERE id_permiso = :id");
        $stmt->bindParam(":id", $id_permiso);
        $stmt->execute(); 
        header("Location: ../gestion_roles.php?success=eliminado");
    } catch (PDOException $e) {
        header("Location: ../gestion_roles.php?error=eliminar");
    }
    exit();
}
?>  