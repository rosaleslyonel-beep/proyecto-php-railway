<?php
session_start();
require_once "config/database.php";
require_once "helpers_resultados_preview.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$id_protocolo = isset($_GET['id_protocolo']) ? (int)$_GET['id_protocolo'] : 0;
$id_usuario = (int)($_SESSION['usuario']['id_usuario'] ?? 0);

if ($id_protocolo <= 0) {
    header("Location: gestion_protocolos.php?error=" . urlencode("Falta el protocolo a procesar."));
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

    $estado = $protocolo['estado'] ?? 'BORRADOR';
    if ($estado === 'BORRADOR') {
        throw new RuntimeException("El protocolo aún está en borrador.");
    }

    $stmt = $conexion->prepare("
        SELECT 
            m.*,
            ma.id_analisis,
            ma.precio_unitario,
            ma.cantidad AS cantidad_analisis,
            COALESCE(ma.estado_resultado, 'ACTIVO') AS estado_resultado,
            ra.id_resultado
        FROM muestras m
        JOIN muestra_analisis ma ON ma.id_muestra = m.id_muestra
        LEFT JOIN resultados_analisis ra
            ON ra.id_muestra = ma.id_muestra
           AND ra.id_analisis = ma.id_analisis
        WHERE m.id_protocolo = ?
        ORDER BY m.id_muestra, ma.id_analisis
    ");
    $stmt->execute([$id_protocolo]);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$filas) {
        throw new RuntimeException("El protocolo no tiene análisis asociados.");
    }

    $conResultado = [];
    $pendientes = [];

    foreach ($filas as $fila) {
        if (!empty($fila['id_resultado'])) {
            $conResultado[] = $fila;
        } elseif (($fila['estado_resultado'] ?? 'ACTIVO') === 'ACTIVO') {
            $pendientes[] = $fila;
        }
    }

    if (!$conResultado) {
        throw new RuntimeException("No hay resultados ingresados para generar una emisión.");
    }

    $resultadoIds = array_values(array_unique(array_map(fn($f) => (int)$f['id_resultado'], $conResultado)));
    $tipoEmision = count($pendientes) > 0 ? 'PARCIAL' : 'FINAL';

    $html = rp_construir_html_preview($conexion, $id_protocolo, [
        'resultado_ids' => $resultadoIds,
        'titulo' => $tipoEmision === 'PARCIAL' ? 'Resultados parciales' : 'Resultados finales',
        'subtitulo_extra' => 'Generado el ' . date('d/m/Y H:i'),
        'mostrar_acciones' => true,
        'volver_url' => 'gestion_protocolos.php?id=' . $id_protocolo . '&tab=tab_resultados'
    ]);

    $idProtocoloDestino = null;

    if ($pendientes) {
        $correlativoBase = $protocolo['correlativo_base'] ?? null;
        if (!$correlativoBase) {
            if (!empty($protocolo['correlativo']) && preg_match('/^(.*?)(-\d{2})$/', $protocolo['correlativo'], $m)) {
                $correlativoBase = $m[1];
            } else {
                $correlativoBase = $protocolo['correlativo'];
            }
        }
        if (!$correlativoBase) {
            throw new RuntimeException("El protocolo no tiene correlativo base para generar seguimiento.");
        }

        $stmt = $conexion->prepare("
            SELECT COALESCE(MAX(no_derivacion), 0) 
            FROM protocolos
            WHERE correlativo_base = ?
        ");
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
                :id_protocolo_padre, :id_protocolo_raiz, 'SEGUIMIENTO', :correlativo_base, :no_derivacion
            )
        ");
        $stmt->execute([
            ':fecha' => $protocolo['fecha'],
            ':telefono' => $protocolo['telefono'],
            ':correo' => $protocolo['correo'],
            ':direccion' => $protocolo['direccion'],
            ':protocolo_no' => $protocolo['protocolo_no'],
            ':observaciones' => $protocolo['observaciones'],
            ':estado_muestra' => $protocolo['estado_muestra'],
            ':entrega_personal' => $protocolo['entrega_personal'],
            ':entrega_correo' => $protocolo['entrega_correo'],
            ':firma_cliente' => $protocolo['firma_cliente'],
            ':firma_recibe' => $protocolo['firma_recibe'],
            ':datos_especificos' => $protocolo['datos_especificos'],
            ':firma_imagen' => $protocolo['firma_imagen'],
            ':id_cliente' => $protocolo['id_cliente'],
            ':id_finca' => $protocolo['id_finca'],
            ':created_by' => $id_usuario,
            ':id_tipo_protocolo' => $protocolo['id_tipo_protocolo'],
            ':tipo_material' => $protocolo['tipo_material'],
            ':mv_remite' => $protocolo['mv_remite'],
            ':departamento' => $protocolo['departamento'],
            ':municipio' => $protocolo['municipio'],
            ':coordenada_vertical' => $protocolo['coordenada_vertical'],
            ':coordenada_horizontal' => $protocolo['coordenada_horizontal'],
            ':procedencia' => $protocolo['procedencia'],
            ':prueba_solicitada' => $protocolo['prueba_solicitada'],
            ':material_solicitado' => $protocolo['material_solicitado'],
            ':correlativo' => $nuevoCorrelativo,
            ':boleta_generada' => $protocolo['boleta_generada'],
            ':boleta_fecha' => $protocolo['boleta_fecha'],
            ':pago_confirmado' => $protocolo['pago_confirmado'],
            ':correlativo_forzado' => $protocolo['correlativo_forzado'],
            ':correlativo_motivo' => $protocolo['correlativo_motivo'],
            ':id_protocolo_padre' => $id_protocolo,
            ':id_protocolo_raiz' => $idRaiz,
            ':correlativo_base' => $correlativoBase,
            ':no_derivacion' => $siguienteNo,
        ]);
        $idProtocoloDestino = (int)$conexion->lastInsertId();

        $mapMuestras = [];
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
            SET estado_resultado = 'TRASLADADO',
                id_protocolo_destino = ?,
                observacion_traslado = ?
            WHERE id_muestra = ? AND id_analisis = ?
        ");

        foreach ($pendientes as $fila) {
            $idMuestraOrigen = (int)$fila['id_muestra'];
            if (!isset($mapMuestras[$idMuestraOrigen])) {
                $stmtNuevaMuestra->execute([$idProtocoloDestino, $id_usuario, $idMuestraOrigen]);
                $mapMuestras[$idMuestraOrigen] = (int)$conexion->lastInsertId();
            }

            $stmtInsertAnalisis->execute([
                $mapMuestras[$idMuestraOrigen],
                (int)$fila['id_analisis'],
                $fila['precio_unitario'],
                $id_usuario,
                $fila['cantidad_analisis'] ?? 1
            ]);

            $stmtUpdateOriginal->execute([
                $idProtocoloDestino,
                'Trasladado al protocolo ' . $nuevoCorrelativo . ' (ID ' . $idProtocoloDestino . ') al generar resultados.',
                $idMuestraOrigen,
                (int)$fila['id_analisis']
            ]);
        }
    }

    $stmt = $conexion->prepare("
        INSERT INTO protocolo_emisiones_resultados (
            id_protocolo, fecha_emision, tipo_emision, correlativo_emitido, archivo_generado,
            observaciones, snapshot_html, resultados_incluidos_json, id_protocolo_destino, created_by, created_date
        ) VALUES (?, NOW(), ?, ?, NULL, ?, ?, ?::jsonb, ?, ?, NOW())
    ");
    $observacionEmision = $tipoEmision === 'PARCIAL'
        ? 'Generación parcial. Se creó un protocolo de seguimiento para resultados pendientes.'
        : 'Generación final del protocolo.';
    $stmt->execute([
        $id_protocolo,
        $tipoEmision,
        $protocolo['correlativo'],
        $observacionEmision,
        $html,
        json_encode($resultadoIds),
        $idProtocoloDestino,
        $id_usuario
    ]);

    $stmt = $conexion->prepare("
        UPDATE protocolos
        SET estado = 'CERRADO',
            updated_by = ?,
            updated_date = NOW(),
            correlativo_base = COALESCE(correlativo_base, correlativo),
            id_protocolo_raiz = COALESCE(id_protocolo_raiz, id_protocolo),
            tipo_derivacion = COALESCE(tipo_derivacion, 'ORIGINAL'),
            no_derivacion = COALESCE(no_derivacion, 0)
        WHERE id_protocolo = ?
    ");
    $stmt->execute([$id_usuario, $id_protocolo]);

    $conexion->commit();

    $msg = $tipoEmision === 'PARCIAL'
        ? 'Resultados generados. Se creó el protocolo de seguimiento con pendientes.'
        : 'Resultados finales generados correctamente.';

    $url = "gestion_protocolos.php?id={$id_protocolo}&tab=tab_resultados&msg=" . urlencode($msg);
    if ($idProtocoloDestino) {
        $url .= "&id_hijo=" . $idProtocoloDestino;
    }
    header("Location: " . $url);
    exit;
} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    header("Location: gestion_protocolos.php?id={$id_protocolo}&tab=tab_resultados&error=" . urlencode($e->getMessage()));
    exit;
}
?>