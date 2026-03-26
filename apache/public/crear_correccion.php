<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$id_usuario = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
$id_protocolo = isset($_POST['id_protocolo']) ? (int)$_POST['id_protocolo'] : 0;
$selecciones = $_POST['selecciones'] ?? [];

if ($id_protocolo <= 0 || empty($selecciones) || !is_array($selecciones)) {
    header("Location: gestion_protocolos.php?id={$id_protocolo}&tab=tab_resultados&error=" . urlencode("Debe seleccionar al menos un análisis para corrección."));
    exit;
}

try {
    $conexion->beginTransaction();

    $stmt = $conexion->prepare("SELECT * FROM protocolos WHERE id_protocolo = ? FOR UPDATE");
    $stmt->execute([$id_protocolo]);
    $protocolo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$protocolo) {
        throw new RuntimeException("No se encontró el protocolo.");
    }

    $correlativoBase = $protocolo['correlativo_base'] ?? null;
    if (!$correlativoBase) {
        if (!empty($protocolo['correlativo']) && preg_match('/^(.*?)(-\d{2})$/', $protocolo['correlativo'], $m)) {
            $correlativoBase = $m[1];
        } else {
            $correlativoBase = $protocolo['correlativo'];
        }
    }
    if (!$correlativoBase) {
        throw new RuntimeException("El protocolo no tiene correlativo base para generar corrección.");
    }

    $stmt = $conexion->prepare("SELECT COALESCE(MAX(no_derivacion), 0) FROM protocolos WHERE correlativo_base = ?");
    $stmt->execute([$correlativoBase]);
    $siguienteNo = ((int)$stmt->fetchColumn()) + 1;
    $nuevoCorrelativo = $correlativoBase . '-' . str_pad((string)$siguienteNo, 2, '0', STR_PAD_LEFT);
    $idRaiz = !empty($protocolo['id_protocolo_raiz']) ? (int)$protocolo['id_protocolo_raiz'] : (int)$id_protocolo;

    $stmt = $conexion->prepare("
        INSERT INTO protocolos (
            fecha, telefono, correo, direccion, protocolo_no, observaciones, estado_muestra,
            entrega_personal, entrega_correo, firma_cliente, firma_recibe, datos_especificos, firma_imagen,
            id_cliente, id_finca, created_by, created_date, id_tipo_protocolo, tipo_material, mv_remite,
            departamento, municipio, coordenada_vertical, coordenada_horizontal, procedencia,
            prueba_solicitada, material_solicitado, correlativo, boleta_generada, boleta_fecha,
            pago_confirmado, correlativo_forzado, correlativo_motivo, estado,
            id_protocolo_padre, id_protocolo_raiz, tipo_derivacion, correlativo_base, no_derivacion
        ) VALUES (
            :fecha, :telefono, :correo, :direccion, :protocolo_no, :observaciones, :estado_muestra,
            :entrega_personal, :entrega_correo, :firma_cliente, :firma_recibe, :datos_especificos, :firma_imagen,
            :id_cliente, :id_finca, :created_by, NOW(), :id_tipo_protocolo, :tipo_material, :mv_remite,
            :departamento, :municipio, :coordenada_vertical, :coordenada_horizontal, :procedencia,
            :prueba_solicitada, :material_solicitado, :correlativo, :boleta_generada, :boleta_fecha,
            :pago_confirmado, :correlativo_forzado, :correlativo_motivo, 'PENDIENTE_RESULTADOS',
            :id_protocolo_padre, :id_protocolo_raiz, 'CORRECCION', :correlativo_base, :no_derivacion
        )
    ");

    $stmt->bindValue(':fecha', $protocolo['fecha']);
    $stmt->bindValue(':telefono', $protocolo['telefono']);
    $stmt->bindValue(':correo', $protocolo['correo']);
    $stmt->bindValue(':direccion', $protocolo['direccion']);
    $stmt->bindValue(':protocolo_no', $protocolo['protocolo_no']);
    $stmt->bindValue(':observaciones', $protocolo['observaciones']);
    $stmt->bindValue(':estado_muestra', $protocolo['estado_muestra']);
    $stmt->bindValue(':entrega_personal', !empty($protocolo['entrega_personal']), PDO::PARAM_BOOL);
    $stmt->bindValue(':entrega_correo', !empty($protocolo['entrega_correo']), PDO::PARAM_BOOL);
    $stmt->bindValue(':firma_cliente', $protocolo['firma_cliente']);
    $stmt->bindValue(':firma_recibe', $protocolo['firma_recibe']);
    $stmt->bindValue(':datos_especificos', $protocolo['datos_especificos']);
    $stmt->bindValue(':firma_imagen', $protocolo['firma_imagen']);
    $stmt->bindValue(':id_cliente', (int)$protocolo['id_cliente'], PDO::PARAM_INT);
    $stmt->bindValue(':id_finca', !empty($protocolo['id_finca']) ? (int)$protocolo['id_finca'] : null, !empty($protocolo['id_finca']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':created_by', $id_usuario, PDO::PARAM_INT);
    $stmt->bindValue(':id_tipo_protocolo', !empty($protocolo['id_tipo_protocolo']) ? (int)$protocolo['id_tipo_protocolo'] : null, !empty($protocolo['id_tipo_protocolo']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':tipo_material', $protocolo['tipo_material']);
    $stmt->bindValue(':mv_remite', $protocolo['mv_remite']);
    $stmt->bindValue(':departamento', $protocolo['departamento']);
    $stmt->bindValue(':municipio', $protocolo['municipio']);
    $stmt->bindValue(':coordenada_vertical', $protocolo['coordenada_vertical']);
    $stmt->bindValue(':coordenada_horizontal', $protocolo['coordenada_horizontal']);
    $stmt->bindValue(':procedencia', $protocolo['procedencia']);
    $stmt->bindValue(':prueba_solicitada', $protocolo['prueba_solicitada']);
    $stmt->bindValue(':material_solicitado', $protocolo['material_solicitado']);
    $stmt->bindValue(':correlativo', $nuevoCorrelativo);
    $stmt->bindValue(':boleta_generada', !empty($protocolo['boleta_generada']), PDO::PARAM_BOOL);
    $stmt->bindValue(':boleta_fecha', !empty($protocolo['boleta_fecha']) ? $protocolo['boleta_fecha'] : null, !empty($protocolo['boleta_fecha']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':pago_confirmado', !empty($protocolo['pago_confirmado']), PDO::PARAM_BOOL);
    $stmt->bindValue(':correlativo_forzado', !empty($protocolo['correlativo_forzado']), PDO::PARAM_BOOL);
    $stmt->bindValue(':correlativo_motivo', $protocolo['correlativo_motivo']);
    $stmt->bindValue(':id_protocolo_padre', $id_protocolo, PDO::PARAM_INT);
    $stmt->bindValue(':id_protocolo_raiz', $idRaiz, PDO::PARAM_INT);
    $stmt->bindValue(':correlativo_base', $correlativoBase);
    $stmt->bindValue(':no_derivacion', $siguienteNo, PDO::PARAM_INT);
    $stmt->execute();

    $idProtocoloDestino = (int)$conexion->lastInsertId();

    $stmtValidar = $conexion->prepare("
        SELECT
            m.*,
            ma.id_analisis,
            ma.precio_unitario,
            ma.cantidad AS cantidad_analisis,
            COALESCE(ma.estado_resultado, 'ACTIVO') AS estado_resultado,
            ra.id_resultado
        FROM muestras m
        JOIN muestra_analisis ma ON ma.id_muestra = m.id_muestra
        JOIN resultados_analisis ra ON ra.id_muestra = ma.id_muestra AND ra.id_analisis = ma.id_analisis
        WHERE m.id_protocolo = ?
          AND m.id_muestra = ?
          AND ma.id_analisis = ?
          AND COALESCE(ma.estado_resultado, 'ACTIVO') = 'ACTIVO'
    ");

    $stmtNuevaMuestra = $conexion->prepare("
        INSERT INTO muestras (
            tipo_muestra, fecha_recepcion, estado_muestra, id_protocolo, id_usuario_responsable,
            buen_estado, autolisis, entrega_personal, entrega_correo, correo_resultado, lesiones_necropsia,
            bacteriologia, virologia, serologia, parasitologico, histologico, micologico, diagnostico_necropsia,
            responsable, id_cliente, especie, edad, descripcion, created_by, created_date, updated_by, updated_date,
            lote, cantidad, variedad, prueba_solicitada, tipo_vacuna, marca_vacuna, dosis, fecha_elaboracion,
            fecha_vencimiento, wssv, tsv, ihhnv, imnv, yhv, mrnv, pvnv, ahpnd_ems, ehp, nhpb, div1,
            createdby, createddate, updatedby, updateddate
        ) SELECT
            tipo_muestra, fecha_recepcion, estado_muestra, ?, id_usuario_responsable,
            buen_estado, autolisis, entrega_personal, entrega_correo, correo_resultado, lesiones_necropsia,
            bacteriologia, virologia, serologia, parasitologico, histologico, micologico, diagnostico_necropsia,
            responsable, id_cliente, especie, edad, descripcion, ?, NOW(), updated_by, updated_date,
            lote, cantidad, variedad, prueba_solicitada, tipo_vacuna, marca_vacuna, dosis, fecha_elaboracion,
            fecha_vencimiento, wssv, tsv, ihhnv, imnv, yhv, mrnv, pvnv, ahpnd_ems, ehp, nhpb, div1,
            createdby, createddate, updatedby, updateddate
        FROM muestras
        WHERE id_muestra = ?
    ");
    $stmtInsertAnalisis = $conexion->prepare("
        INSERT INTO muestra_analisis (
            id_muestra, id_analisis, precio_unitario, created_by, created_date, cantidad,
            estado_resultado, id_protocolo_destino, observacion_traslado
        ) VALUES (?, ?, ?, ?, NOW(), ?, 'ACTIVO', NULL, NULL)
    ");
    $stmtUpdateOriginal = $conexion->prepare("
        UPDATE muestra_analisis
        SET estado_resultado = 'CORREGIDO',
            id_protocolo_destino = ?,
            observacion_traslado = ?
        WHERE id_muestra = ? AND id_analisis = ?
    ");

    $mapMuestras = [];
    $copiados = 0;

    foreach ($selecciones as $sel) {
        if (strpos($sel, '|') === false) {
            continue;
        }
        [$idMuestraOrigen, $idAnalisis] = array_map('intval', explode('|', $sel, 2));

        $stmtValidar->execute([$id_protocolo, $idMuestraOrigen, $idAnalisis]);
        $fila = $stmtValidar->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            continue;
        }

        if (!isset($mapMuestras[$idMuestraOrigen])) {
            $stmtNuevaMuestra->execute([$idProtocoloDestino, $id_usuario, $idMuestraOrigen]);
            $mapMuestras[$idMuestraOrigen] = (int)$conexion->lastInsertId();
        }

        $stmtInsertAnalisis->execute([
            $mapMuestras[$idMuestraOrigen],
            $idAnalisis,
            $fila['precio_unitario'],
            $id_usuario,
            $fila['cantidad_analisis'] ?? 1
        ]);

        $stmtUpdateOriginal->execute([
            $idProtocoloDestino,
            'Corregido mediante el protocolo ' . $nuevoCorrelativo . ' (ID ' . $idProtocoloDestino . ').',
            $idMuestraOrigen,
            $idAnalisis
        ]);

        $copiados++;
    }

    if ($copiados === 0) {
        throw new RuntimeException("No fue posible copiar los análisis seleccionados para corrección.");
    }

    $conexion->commit();

    header("Location: gestion_protocolos.php?id={$idProtocoloDestino}&tab=tab_resultados&msg=" . urlencode("Se creó el protocolo de corrección {$nuevoCorrelativo}."));
    exit;
} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    header("Location: gestion_protocolos.php?id={$id_protocolo}&tab=tab_resultados&error=" . urlencode($e->getMessage()));
    exit;
}
