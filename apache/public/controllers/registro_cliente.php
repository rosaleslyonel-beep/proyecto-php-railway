<?php
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
// Validar reCAPTCHA
$recaptcha_secret = "6Le7gCIrAAAAAENW1puUzi0h3lXDeB9Jy4QRMgUs"; // tu clave secreta
$recaptcha_response = $_POST['g-recaptcha-response'];

$verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
$captcha_success = json_decode($verify);

if (!$captcha_success->success) {
    // Falló el captcha
    header("Location: ../registro_cliente.php?error=captcha");
    exit();
}


    $nombre_cliente = $_POST['nombre_cliente'];
 
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $direccion = $_POST['direccion'];
    $usuario = $_POST['usuario'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);

    try {
        $conexion->beginTransaction();

        // Insertar cliente
        $stmt = $conexion->prepare("INSERT INTO clientes (nombre ,    telefono, correo, direccion, created_date)
                                    VALUES (:nombre,   :telefono, :correo, :direccion, NOW())");
        $stmt->execute([
            ':nombre' => $nombre_cliente, 
            ':telefono' => $telefono,
            ':correo' => $correo,
            ':direccion' => $direccion
        ]);

        $id_cliente = $conexion->lastInsertId();

        // Insertar usuario
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre , correo, contrasena, rol, id_cliente, estado, created_date)
                                    VALUES (:usuario, :correo, :contrasena, 'cliente', :id_cliente, TRUE, NOW())");
        $stmt->execute([
            ':usuario' => $usuario,
            ':correo' => $correo,
            ':contrasena' => $contrasena,
            ':id_cliente' => $id_cliente
        ]);

        $conexion->commit();
        header("Location: ../index.php?registro=exito");
        
        exit();
    } catch (PDOException $e) {

        if ($e->getCode() == '23505') { // código de error para claves únicas en PostgreSQL
            header("Location: ../registro_cliente.php?msg=correo_duplicado");
        } else {
            header("Location: ../registro_cliente.php?msg=error");
        }
        $conexion->rollBack();
        echo "<h3>Error al registrar el cliente:</h3>";
        echo "<pre>" . $e->getMessage() . "</pre>";
        
        exit();
    }
}
