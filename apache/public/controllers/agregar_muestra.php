<?php
require_once "../config/database.php";

// Recibir datos del formulario
$id_muestra = $_POST['id_muestra'] ?? '';
$id_protocolo = $_POST['id_protocolo'];

$tipo_muestra = $_POST['tipo_muestra'] ?? '';
$lote = $_POST['lote'] ?? '';
$cantidad = $_POST['cantidad'] ?? '';
$edad = $_POST['edad'] ?? '';
$variedad = $_POST['variedad'] ?? '';
$prueba_solicitada = $_POST['prueba_solicitada'] ?? '';

// Vacunas
$tipo_vacuna = $_POST['tipo_vacuna'] ?? '';
$marca_vacuna = $_POST['marca_vacuna'] ?? '';
$dosis = $_POST['dosis'] ?? '';
$fecha_elaboracion = !empty($_POST['fecha_elaboracion']) ? $_POST['fecha_elaboracion'] : null;
$fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;

// Camarones (checkboxes)
function check($campo) {
    return isset($_POST[$campo]) ? 1 : 0;
}
$wssv = check('wssv');
$tsv = check('tsv');
$ihhnv = check('ihhnv');
$imnv = check('imnv');
$yhv = check('yhv');
$mrnv = check('mrnv');
$pvnv = check('pvnv');
$ahpnd_ems = check('ahpnd_ems');
$ehp = check('ehp');
$nhpb = check('nhpb');
$div1 = check('div1');

try {
    if ($id_muestra) {
        // Actualizar muestra existente
        $stmt = $conexion->prepare("UPDATE muestras SET 
            tipo_muestra = ?, lote = ?, cantidad = ?, edad = ?, variedad = ?, prueba_solicitada = ?,
            tipo_vacuna = ?, marca_vacuna = ?, dosis = ?, fecha_elaboracion = ?, fecha_vencimiento = ?,
            wssv = ?, tsv = ?, ihhnv = ?, imnv = ?, yhv = ?, mrnv = ?, pvnv = ?, ahpnd_ems = ?, ehp = ?, nhpb = ?, div1 = ?
            WHERE id_muestra = ?");

        $stmt->execute([
            $tipo_muestra, $lote, $cantidad, $edad, $variedad, $prueba_solicitada,
            $tipo_vacuna, $marca_vacuna, $dosis, $fecha_elaboracion, $fecha_vencimiento,
            $wssv, $tsv, $ihhnv, $imnv, $yhv, $mrnv, $pvnv, $ahpnd_ems, $ehp, $nhpb, $div1,
            $id_muestra
        ]);
            include 'agregar_analisis_muestra.php';
       
    } else {
        // Insertar nueva muestra
        $stmt = $conexion->prepare("INSERT INTO muestras (
            id_protocolo, tipo_muestra, lote, cantidad, edad, variedad, prueba_solicitada,
            tipo_vacuna, marca_vacuna, dosis, fecha_elaboracion, fecha_vencimiento,
            wssv, tsv, ihhnv, imnv, yhv, mrnv, pvnv, ahpnd_ems, ehp, nhpb, div1
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->execute([
            $id_protocolo, $tipo_muestra, $lote, $cantidad, $edad, $variedad, $prueba_solicitada,
            $tipo_vacuna, $marca_vacuna, $dosis, $fecha_elaboracion, $fecha_vencimiento,
            $wssv, $tsv, $ihhnv, $imnv, $yhv, $mrnv, $pvnv, $ahpnd_ems, $ehp, $nhpb, $div1
        ]);
            include 'agregar_analisis_muestra.php';
     
    }

    // Regresar al protocolo
    header("Location: ../gestion_protocolos.php?id=".$id_protocolo."&tab=tab_muestras");
    exit;

} catch (PDOException $e) {
    echo "Error al guardar la muestra: " . $e->getMessage();
    header("Location: ../gestion_protocolos.php?id=".$id_protocolo."&tab=tab_muestras&error=1");
}
