<?php
require_once "../config/database.php";
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id = isset($_POST['id_protocolo']) ? intval($_POST['id_protocolo']) : null;

$rol = $_SESSION['usuario']['rol'];
$id_cliente = ($rol === 'cliente') 
    ? $_SESSION['usuario']['id_cliente'] 
    : ($_POST['id_cliente'] ?? null);

$id_finca = $_POST['id_finca'];
$id_tipo_protocolo = $_POST['id_tipo_protocolo'] ?? null;
$fecha = $_POST['fecha'];
$tipo_material = $_POST['tipo_material'] ?? '';
$mv_remite = $_POST['mv_remite'] ?? '';
$correo = $_POST['correo'] ?? '';
$departamento = $_POST['departamento'] ?? '';
$municipio = $_POST['municipio'] ?? '';
$coordenada_vertical = $_POST['coordenada_vertical'] ?? '';
$coordenada_horizontal = $_POST['coordenada_horizontal'] ?? '';
$procedencia = $_POST['procedencia'] ?? '';
$prueba_solicitada = $_POST['prueba_solicitada'] ?? '';
$material_solicitado = $_POST['material_solicitado'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$estado_muestra = $_POST['estado_muestra'] ?? '';
$entrega_personal = isset($_POST['entrega_personal']) ? true : false;
$entrega_correo = isset($_POST['entrega_correo']) ? true : false;
$entrega_personal = !empty($_POST['entrega_personal']) ? true : false;
$entrega_correo = !empty($_POST['entrega_correo']) ? true : false;
$firma = $_POST['firma_imagen'] ?? '';

$id_usuario = $_SESSION['usuario']['id_usuario'] ?? 1;
$fecha_hoy = date("Y-m-d H:i:s");

try {
    if ($id) {
        $stmt = $conexion->prepare("
            UPDATE protocolos SET 
                id_cliente = :id_cliente,
                id_finca = :id_finca,
                id_tipo_protocolo = :id_tipo_protocolo,
                fecha = :fecha,
                tipo_material = :tipo_material,
                mv_remite = :mv_remite,
                correo = :correo,
                departamento = :departamento,
                municipio = :municipio,
                coordenada_vertical = :coordenada_vertical,
                coordenada_horizontal = :coordenada_horizontal,
                procedencia = :procedencia,
                prueba_solicitada = :prueba_solicitada,
                material_solicitado = :material_solicitado,
                observaciones = :observaciones,
                estado_muestra = :estado_muestra,
                entrega_personal = :entrega_personal,
                entrega_correo = :entrega_correo,
                firma_imagen = :firma,
                updated_by = :usuario,
                updated_date = :fecha_act
            WHERE id_protocolo = :id
        ");
        $stmt->execute([
            ':id_cliente' => $id_cliente,
            ':id_finca' => $id_finca,
            ':id_tipo_protocolo' => $id_tipo_protocolo,
            ':fecha' => $fecha,
            ':tipo_material' => $tipo_material,
            ':mv_remite' => $mv_remite,
            ':correo' => $correo,
            ':departamento' => $departamento,
            ':municipio' => $municipio,
            ':coordenada_vertical' => $coordenada_vertical,
            ':coordenada_horizontal' => $coordenada_horizontal,
            ':procedencia' => $procedencia,
            ':prueba_solicitada' => $prueba_solicitada,
            ':material_solicitado' => $material_solicitado,
            ':observaciones' => $observaciones,
            ':estado_muestra' => $estado_muestra,
            ':entrega_personal' => $entrega_personal ? 'true' : 'false',
            ':entrega_correo' => $entrega_correo ? 'true' : 'false',
            ':firma' => $firma,
            ':usuario' => $id_usuario,
            ':fecha_act' => $fecha_hoy,
            ':id' => $id
        ]);
    } else {
        $stmt = $conexion->prepare("
            INSERT INTO protocolos (
                id_cliente, id_finca, id_tipo_protocolo, fecha,
                tipo_material, mv_remite, correo, departamento, municipio,
                coordenada_vertical, coordenada_horizontal, procedencia,
                prueba_solicitada, material_solicitado, observaciones,
                estado_muestra, entrega_personal, entrega_correo, firma_imagen,
                created_by, created_date
            ) VALUES (
                :id_cliente, :id_finca, :id_tipo_protocolo, :fecha,
                :tipo_material, :mv_remite, :correo, :departamento, :municipio,
                :coordenada_vertical, :coordenada_horizontal, :procedencia,
                :prueba_solicitada, :material_solicitado, :observaciones,
                :estado_muestra, :entrega_personal, :entrega_correo, :firma,
                :usuario, :fecha_act
            )
        ");
        $stmt->execute([
            ':id_cliente' => $id_cliente,
            ':id_finca' => $id_finca,
            ':id_tipo_protocolo' => $id_tipo_protocolo,
            ':fecha' => $fecha,
            ':tipo_material' => $tipo_material,
            ':mv_remite' => $mv_remite,
            ':correo' => $correo,
            ':departamento' => $departamento,
            ':municipio' => $municipio,
            ':coordenada_vertical' => $coordenada_vertical,
            ':coordenada_horizontal' => $coordenada_horizontal,
            ':procedencia' => $procedencia,
            ':prueba_solicitada' => $prueba_solicitada,
            ':material_solicitado' => $material_solicitado,
            ':observaciones' => $observaciones,
            ':estado_muestra' => $estado_muestra,
            ':entrega_personal' => $entrega_personal ? 'true' : 'false',
            ':entrega_correo' => $entrega_correo ? 'true' : 'false',            
            ':firma' => $firma,
            ':usuario' => $id_usuario,
            ':fecha_act' => $fecha_hoy
        ]);
        $id = $conexion->lastInsertId();
    }

    header("Location: ../gestion_protocolos.php?id=$id");
    exit();

} catch (PDOException $e) {
    echo "<h3>Error al guardar el protocolo:</h3><pre>" . $e->getMessage() . "</pre>";
    exit();
}
