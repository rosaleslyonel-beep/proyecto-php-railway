<?php
require_once "../config/database.php";
require_once "../config/helpers.php";
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$id_muestra   = $_POST['id_muestra'] ?? null;
$id_protocolo = $_POST['id_protocolo'] ?? null;
$usuario_id = $_SESSION['usuario']['id_usuario'] ?? null;

if (!$id_protocolo || !is_numeric($id_protocolo)) {
    echo "Error: Falta ID de protocolo.";
    exit();
}

$tipo_muestra = trim($_POST['tipo_muestra'] ?? '');
$lote         = trim($_POST['lote'] ?? '');
$cantidad     = trim($_POST['cantidad'] ?? '');
$edad         = trim($_POST['edad'] ?? '');
$variedad     = trim($_POST['variedad'] ?? '');

$tipo_vacuna       = trim($_POST['tipo_vacuna'] ?? '');
$marca_vacuna      = trim($_POST['marca_vacuna'] ?? '');
$dosis             = trim($_POST['dosis'] ?? '');
$fecha_elaboracion = $_POST['fecha_elaboracion'] ?? null;
$fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;

$wssv      = isset($_POST['wssv']) ? true : false;
$tsv       = isset($_POST['tsv']) ? true : false;
$ihhnv     = isset($_POST['ihhnv']) ? true : false;
$imnv      = isset($_POST['imnv']) ? true : false;
$yhv       = isset($_POST['yhv']) ? true : false;
$mrnv      = isset($_POST['mrnv']) ? true : false;
$pvnv      = isset($_POST['pvnv']) ? true : false;
$ahpnd_ems = isset($_POST['ahpnd_ems']) ? true : false;
$ehp       = isset($_POST['ehp']) ? true : false;
$nhpb      = isset($_POST['nhpb']) ? true : false;
$div1      = isset($_POST['div1']) ? true : false;

$id_usuario = $_SESSION['usuario']['id_usuario'] ?? 1;
$fecha_hoy  = date("Y-m-d H:i:s");

try {
    if (!empty($id_muestra) && is_numeric($id_muestra)) {
      $stmt = $conexion->prepare("
    UPDATE muestras
       SET tipo_muestra = :tipo_muestra,
           lote = :lote,
           cantidad = :cantidad,
           edad = :edad,
           variedad = :variedad,
           tipo_vacuna = :tipo_vacuna,
           marca_vacuna = :marca_vacuna,
           dosis = :dosis,
           fecha_elaboracion = :fecha_elaboracion,
           fecha_vencimiento = :fecha_vencimiento,
           wssv = :wssv,
           tsv = :tsv,
           ihhnv = :ihhnv,
           imnv = :imnv,
           yhv = :yhv,
           mrnv = :mrnv,
           pvnv = :pvnv,
           ahpnd_ems = :ahpnd_ems,
           ehp = :ehp,
           nhpb = :nhpb,
           div1 = :div1,
           updated_by = :updated_by,
           updated_date = :updated_date
     WHERE id_muestra = :id_muestra
");
$stmt->bindValue(':tipo_muestra', $tipo_muestra, PDO::PARAM_STR);
$stmt->bindValue(':lote', $lote, PDO::PARAM_STR);
$stmt->bindValue(':cantidad', $cantidad);
$stmt->bindValue(':edad', $edad);

$stmt->bindValue(':variedad', $variedad !== '' ? $variedad : null, $variedad !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
$stmt->bindValue(':tipo_vacuna', $tipo_vacuna !== '' ? $tipo_vacuna : null, $tipo_vacuna !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
$stmt->bindValue(':marca_vacuna', $marca_vacuna !== '' ? $marca_vacuna : null, $marca_vacuna !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
$stmt->bindValue(':dosis', $dosis !== '' ? $dosis : null, $dosis !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

$stmt->bindValue(':fecha_elaboracion', $fecha_elaboracion ?: null, $fecha_elaboracion ? PDO::PARAM_STR : PDO::PARAM_NULL);
$stmt->bindValue(':fecha_vencimiento', $fecha_vencimiento ?: null, $fecha_vencimiento ? PDO::PARAM_STR : PDO::PARAM_NULL);

# 🔥 IMPORTANTE: BOOLEANOS
$stmt->bindValue(':wssv', $wssv, PDO::PARAM_BOOL);
$stmt->bindValue(':tsv', $tsv, PDO::PARAM_BOOL);
$stmt->bindValue(':ihhnv', $ihhnv, PDO::PARAM_BOOL);
$stmt->bindValue(':imnv', $imnv, PDO::PARAM_BOOL);
$stmt->bindValue(':yhv', $yhv, PDO::PARAM_BOOL);
$stmt->bindValue(':mrnv', $mrnv, PDO::PARAM_BOOL);
$stmt->bindValue(':pvnv', $pvnv, PDO::PARAM_BOOL);
$stmt->bindValue(':ahpnd_ems', $ahpnd_ems, PDO::PARAM_BOOL);
$stmt->bindValue(':ehp', $ehp, PDO::PARAM_BOOL);
$stmt->bindValue(':nhpb', $nhpb, PDO::PARAM_BOOL);
$stmt->bindValue(':div1', $div1, PDO::PARAM_BOOL);

$stmt->bindValue(':updated_by', $id_usuario, PDO::PARAM_INT);
$stmt->bindValue(':updated_date', $fecha_hoy, PDO::PARAM_STR);
$stmt->bindValue(':id_muestra', $id_muestra, PDO::PARAM_INT);

$stmt->execute();
    } else {
        $stmt = $conexion->prepare("
                INSERT INTO muestras (
                    id_protocolo, tipo_muestra, lote, cantidad, edad, variedad,
                    tipo_vacuna, marca_vacuna, dosis, fecha_elaboracion, fecha_vencimiento,
                    wssv, tsv, ihhnv, imnv, yhv, mrnv, pvnv, ahpnd_ems, ehp, nhpb, div1,
                    created_by, created_date
                ) VALUES (
                    :id_protocolo, :tipo_muestra, :lote, :cantidad, :edad, :variedad,
                    :tipo_vacuna, :marca_vacuna, :dosis, :fecha_elaboracion, :fecha_vencimiento,
                    :wssv, :tsv, :ihhnv, :imnv, :yhv, :mrnv, :pvnv, :ahpnd_ems, :ehp, :nhpb, :div1,
                    :created_by, :created_date
                )
            ");

            $stmt->bindValue(':id_protocolo', $id_protocolo, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_muestra', $tipo_muestra, PDO::PARAM_STR);
            $stmt->bindValue(':lote', $lote, PDO::PARAM_STR);
            $stmt->bindValue(':cantidad', $cantidad);
            $stmt->bindValue(':edad', $edad);
            $stmt->bindValue(':variedad', $variedad !== '' ? $variedad : null, $variedad !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':tipo_vacuna', $tipo_vacuna !== '' ? $tipo_vacuna : null, $tipo_vacuna !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':marca_vacuna', $marca_vacuna !== '' ? $marca_vacuna : null, $marca_vacuna !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':dosis', $dosis !== '' ? $dosis : null, $dosis !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':fecha_elaboracion', $fecha_elaboracion ?: null, $fecha_elaboracion ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':fecha_vencimiento', $fecha_vencimiento ?: null, $fecha_vencimiento ? PDO::PARAM_STR : PDO::PARAM_NULL);

            $stmt->bindValue(':wssv', $wssv, PDO::PARAM_BOOL);
            $stmt->bindValue(':tsv', $tsv, PDO::PARAM_BOOL);
            $stmt->bindValue(':ihhnv', $ihhnv, PDO::PARAM_BOOL);
            $stmt->bindValue(':imnv', $imnv, PDO::PARAM_BOOL);
            $stmt->bindValue(':yhv', $yhv, PDO::PARAM_BOOL);
            $stmt->bindValue(':mrnv', $mrnv, PDO::PARAM_BOOL);
            $stmt->bindValue(':pvnv', $pvnv, PDO::PARAM_BOOL);
            $stmt->bindValue(':ahpnd_ems', $ahpnd_ems, PDO::PARAM_BOOL);
            $stmt->bindValue(':ehp', $ehp, PDO::PARAM_BOOL);
            $stmt->bindValue(':nhpb', $nhpb, PDO::PARAM_BOOL);
            $stmt->bindValue(':div1', $div1, PDO::PARAM_BOOL);

            $stmt->bindValue(':created_by', $id_usuario, PDO::PARAM_INT);
            $stmt->bindValue(':created_date', $fecha_hoy, PDO::PARAM_STR);

            $stmt->execute();

        $id_muestra = $conexion->lastInsertId();
    }

    if (!empty($_POST['analisis_ids']) && is_array($_POST['analisis_ids'])) {
        $stmtDel = $conexion->prepare("DELETE FROM muestra_analisis WHERE id_muestra = ?");
        $stmtDel->execute([$id_muestra]);

        
         $stmt_insert = $conexion->prepare("
            INSERT INTO muestra_analisis (id_muestra, id_analisis, precio_unitario, created_by, created_date,cantidad)
            SELECT ?, id_analisis, precio, ?, NOW(),1
            FROM analisis_laboratorio
            WHERE id_analisis = ?
        ");

        foreach ($_POST['analisis_ids']  as $id_analisis) {
            $stmt_insert->execute([$id_muestra, $usuario_id, $id_analisis]);
        }

    }

    header("Location: ../gestion_protocolos.php?id={$id_protocolo}&tab=tab_muestras");
    exit();

} catch (Throwable $e) {
    echo "Error al guardar muestra: " . $e->getMessage();
}