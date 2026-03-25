<?php
require_once "config/session.php";
require_once "config/helpers.php";
require_once "config/database.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit();
}

$usuario = $_SESSION['usuario'];
$rolNombre = strtolower(trim($usuario['rol_nombre'] ?? ''));
$idUsuario = (int)($usuario['id_usuario'] ?? 0);
$idClienteSesion = $usuario['id_cliente'] ?? null;
$idRol = $usuario['id_rol'] ?? null;

$fechaDesde = trim($_GET['fecha_desde'] ?? '');
$fechaHasta = trim($_GET['fecha_hasta'] ?? '');
$estadoFiltro = trim($_GET['estado'] ?? '');

if ($fechaHasta === '') {
    $fechaHasta = date('Y-m-d');
}
if ($fechaDesde === '') {
    $fechaDesde = date('Y-m-d', strtotime('-30 days'));
}

function obtenerProtocolosDashboard(PDO $conexion, string $sql, array $params = []): array {
    $stmt = $conexion->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function construirFiltroFechas(string $alias, array &$params, string $fechaDesde, string $fechaHasta): string {
    $filtro = "";
    if ($fechaDesde !== '') {
        $filtro .= " AND DATE($alias.created_date) >= :fecha_desde";
        $params[':fecha_desde'] = $fechaDesde;
    }
    if ($fechaHasta !== '') {
        $filtro .= " AND DATE($alias.created_date) <= :fecha_hasta";
        $params[':fecha_hasta'] = $fechaHasta;
    }
    return $filtro;
}

function construirFiltroEstado(string $alias, array &$params, string $estadoFiltro): string {
    if ($estadoFiltro === '') {
        return "";
    }
    $params[':estado_filtro'] = $estadoFiltro;
    return " AND $alias.estado = :estado_filtro";
}

function renderTarjetasProtocolos(array $protocolos, bool $irResultados = false): void {
    if (empty($protocolos)) {
        echo '<div class="sin-registros">No hay protocolos en este rango.</div>';
        return;
    }

    foreach ($protocolos as $p) {
        $url = 'gestion_protocolos.php?id=' . (int)$p['id_protocolo'];
        if ($irResultados) {
            $url .= '&tab=tab_resultados';
        }

        $correlativo = trim((string)($p['correlativo'] ?? ''));
        $estado = $p['estado'] ?? 'BORRADOR';
        $cliente = $p['cliente'] ?? '';
        $finca = $p['finca'] ?? '';
        $tipo = $p['tipo_protocolo'] ?? '';
        $fecha = $p['fecha'] ?? '';
        $muestras = $p['total_muestras'] ?? 0;
        $analisis = $p['total_analisis'] ?? null;

        echo '<a class="tarjeta-protocolo" href="' . htmlspecialchars($url) . '">';
        echo '  <div class="tarjeta-header">';
        echo '      <span class="etiqueta estado-' . strtolower(htmlspecialchars($estado)) . '">' . htmlspecialchars($estado) . '</span>';
        echo '      <span class="correlativo">' . htmlspecialchars($correlativo !== '' ? $correlativo : 'Sin correlativo') . '</span>';
        echo '  </div>';
        echo '  <div class="tarjeta-body">';
        echo '      <div><strong>Protocolo:</strong> ' . (int)$p['id_protocolo'] . '</div>';
        echo '      <div><strong>Fecha:</strong> ' . htmlspecialchars($fecha) . '</div>';
        echo '      <div><strong>Cliente:</strong> ' . htmlspecialchars($cliente) . '</div>';
        echo '      <div><strong>Finca:</strong> ' . htmlspecialchars($finca !== '' ? $finca : '—') . '</div>';
        echo '      <div><strong>Tipo:</strong> ' . htmlspecialchars($tipo !== '' ? $tipo : '—') . '</div>';
        echo '      <div><strong>Muestras:</strong> ' . (int)$muestras . '</div>';
        if ($analisis !== null) {
            echo '  <div><strong>Análisis asignados:</strong> ' . (int)$analisis . '</div>';
        }
        echo '  </div>';
        echo '  <div class="tarjeta-footer">Abrir protocolo</div>';
        echo '</a>';
    }
}

$paramsBase = [];
$filtroFechas = construirFiltroFechas('p', $paramsBase, $fechaDesde, $fechaHasta);
$filtroEstado = construirFiltroEstado('p', $paramsBase, $estadoFiltro);

$selectBase = "
    SELECT
        p.id_protocolo,
        p.correlativo,
        p.estado,
        TO_CHAR(p.created_date, 'YYYY-MM-DD') AS fecha,
        c.nombre AS cliente,
        COALESCE(f.nombre_finca, '') AS finca,
        COALESCE(tp.nombre_tipo, '') AS tipo_protocolo,
        COUNT(DISTINCT m.id_muestra) AS total_muestras
    FROM protocolos p
    INNER JOIN clientes c ON c.id_cliente = p.id_cliente
    LEFT JOIN fincas f ON f.id_finca = p.id_finca
    LEFT JOIN tipos_protocolo tp ON tp.id_tipo_protocolo = p.id_tipo_protocolo
    LEFT JOIN muestras m ON m.id_protocolo = p.id_protocolo
";
$groupBase = "
    GROUP BY p.id_protocolo, p.correlativo, p.estado, p.created_date, c.nombre, f.nombre_finca, tp.nombre_tipo
    ORDER BY p.created_date DESC, p.id_protocolo DESC
";

$secciones = [];
$irResultados = false;

if ($rolNombre === 'cliente') {
    $params = $paramsBase;
    $params[':id_cliente'] = $idClienteSesion;

    $sqlProceso = $selectBase . "
        WHERE p.id_cliente = :id_cliente
          AND p.estado IN ('BORRADOR', 'PENDIENTE_RESULTADOS')
          $filtroFechas
          $filtroEstado
    " . $groupBase;

    $sqlFinalizados = $selectBase . "
        WHERE p.id_cliente = :id_cliente
          AND p.estado = 'CERRADO'
          $filtroFechas
          $filtroEstado
    " . $groupBase;

    $secciones[] = [
        'titulo' => 'Protocolos en proceso',
        'datos' => obtenerProtocolosDashboard($conexion, $sqlProceso, $params),
        'ir_resultados' => false,
    ];
    $secciones[] = [
        'titulo' => 'Protocolos finalizados',
        'datos' => obtenerProtocolosDashboard($conexion, $sqlFinalizados, $params),
        'ir_resultados' => true,
    ];
} elseif ($rolNombre === 'recepcion' || $rolNombre === 'admin' || $rolNombre === 'administrador') {
    $sqlBorrador = $selectBase . "
        WHERE p.estado = 'BORRADOR'
          $filtroFechas
          $filtroEstado
    " . $groupBase;

    $sqlPendientes = $selectBase . "
        WHERE p.estado = 'PENDIENTE_RESULTADOS'
          $filtroFechas
          $filtroEstado
    " . $groupBase;

    $secciones[] = [
        'titulo' => 'Pendientes de ingreso',
        'datos' => obtenerProtocolosDashboard($conexion, $sqlBorrador, $paramsBase),
        'ir_resultados' => false,
    ];
    $secciones[] = [
        'titulo' => 'Listos para resultados',
        'datos' => obtenerProtocolosDashboard($conexion, $sqlPendientes, $paramsBase),
        'ir_resultados' => true,
    ];
} else {
    $irResultados = true;
    $params = $paramsBase;
    $params[':id_rol'] = $idRol;

    $sqlAnalista = "
        SELECT DISTINCT
            p.id_protocolo,
            p.correlativo,
            p.estado,
            TO_CHAR(p.created_date, 'YYYY-MM-DD') AS fecha,
            c.nombre AS cliente,
            COALESCE(f.nombre_finca, '') AS finca,
            COALESCE(tp.nombre_tipo, '') AS tipo_protocolo,
            COUNT(DISTINCT m.id_muestra) OVER (PARTITION BY p.id_protocolo) AS total_muestras,
            COUNT(DISTINCT ma.id_analisis) OVER (PARTITION BY p.id_protocolo) AS total_analisis
        FROM protocolos p
        INNER JOIN clientes c ON c.id_cliente = p.id_cliente
        LEFT JOIN fincas f ON f.id_finca = p.id_finca
        LEFT JOIN tipos_protocolo tp ON tp.id_tipo_protocolo = p.id_tipo_protocolo
        INNER JOIN muestras m ON m.id_protocolo = p.id_protocolo
        INNER JOIN muestra_analisis ma ON ma.id_muestra = m.id_muestra
        INNER JOIN analisis_roles ar ON ar.id_analisis = ma.id_analisis
        WHERE ar.id_rol = :id_rol
          AND p.estado = 'PENDIENTE_RESULTADOS'
          $filtroFechas
          $filtroEstado
        ORDER BY p.created_date DESC, p.id_protocolo DESC
    ";

    $secciones[] = [
        'titulo' => 'Protocolos asignados para resultados',
        'datos' => obtenerProtocolosDashboard($conexion, $sqlAnalista, $params),
        'ir_resultados' => true,
    ];
}

include "views/header.php";
include "views/menu.php";
?>

<div id="main-content" class="dashboard-wrapper">
    <div class="dashboard-top">
        <div>
            <h2>Dashboard</h2>
            <p>
                Bienvenido, <?= htmlspecialchars($usuario['nombre'] ?? '') ?>
                (Rol: <?= htmlspecialchars(ucfirst($usuario['rol_nombre'] ?? '')) ?>)
            </p>
        </div>

        <form method="GET" class="filtros-dashboard">
            <div class="campo-filtro">
                <label for="fecha_desde">Desde</label>
                <input type="date" name="fecha_desde" id="fecha_desde" value="<?= htmlspecialchars($fechaDesde) ?>">
            </div>
            <div class="campo-filtro">
                <label for="fecha_hasta">Hasta</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" value="<?= htmlspecialchars($fechaHasta) ?>">
            </div>
            <div class="campo-filtro">
                <label for="estado">Estado</label>
                <select name="estado" id="estado">
                    <option value="">Todos</option>
                    <option value="BORRADOR" <?= $estadoFiltro === 'BORRADOR' ? 'selected' : '' ?>>BORRADOR</option>
                    <option value="PENDIENTE_RESULTADOS" <?= $estadoFiltro === 'PENDIENTE_RESULTADOS' ? 'selected' : '' ?>>PENDIENTE_RESULTADOS</option>
                    <option value="CERRADO" <?= $estadoFiltro === 'CERRADO' ? 'selected' : '' ?>>CERRADO</option>
                </select>
            </div>
            <div class="campo-filtro acciones-filtro">
                <button type="submit">Filtrar</button>
                <a href="dashboard.php">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="atajos-fecha">
        <a href="dashboard.php?fecha_desde=<?= urlencode(date('Y-m-d')) ?>&fecha_hasta=<?= urlencode(date('Y-m-d')) ?>">Hoy</a>
        <a href="dashboard.php?fecha_desde=<?= urlencode(date('Y-m-d', strtotime('-7 days'))) ?>&fecha_hasta=<?= urlencode(date('Y-m-d')) ?>">Últimos 7 días</a>
        <a href="dashboard.php?fecha_desde=<?= urlencode(date('Y-m-d', strtotime('-30 days'))) ?>&fecha_hasta=<?= urlencode(date('Y-m-d')) ?>">Últimos 30 días</a>
        <a href="dashboard.php?fecha_desde=<?= urlencode(date('Y-m-d', strtotime('-90 days'))) ?>&fecha_hasta=<?= urlencode(date('Y-m-d')) ?>">Últimos 90 días</a>
    </div>

    <?php foreach ($secciones as $seccion): ?>
        <section class="bloque-dashboard">
            <div class="bloque-header">
                <h3><?= htmlspecialchars($seccion['titulo']) ?></h3>
                <span class="contador"><?= count($seccion['datos']) ?> protocolo(s)</span>
            </div>
            <div class="grid-tarjetas">
                <?php renderTarjetasProtocolos($seccion['datos'], $seccion['ir_resultados']); ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<style>
.dashboard-wrapper {
    padding: 20px;
}
.dashboard-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.filtros-dashboard {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: flex-end;
    background: #f7f7f7;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 12px;
}
.campo-filtro {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.campo-filtro input,
.campo-filtro select {
    padding: 8px 10px;
    min-width: 160px;
}
.acciones-filtro {
    flex-direction: row;
    gap: 8px;
}
.acciones-filtro button,
.acciones-filtro a,
.atajos-fecha a {
    text-decoration: none;
    border: none;
    background: #00695c;
    color: #fff;
    padding: 9px 14px;
    border-radius: 6px;
    cursor: pointer;
}
.acciones-filtro a {
    background: #607d8b;
}
.atajos-fecha {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.bloque-dashboard {
    margin-bottom: 28px;
}
.bloque-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.contador {
    color: #666;
    font-size: 14px;
}
.grid-tarjetas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}
.tarjeta-protocolo {
    display: block;
    text-decoration: none;
    color: #222;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: transform .15s ease, box-shadow .15s ease;
}
.tarjeta-protocolo:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.10);
}
.tarjeta-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    gap: 10px;
}
.etiqueta {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: bold;
}
.estado-borrador { background: #fff3cd; color: #856404; }
.estado-pendiente_resultados { background: #d1ecf1; color: #0c5460; }
.estado-cerrado { background: #d4edda; color: #155724; }
.correlativo {
    font-size: 13px;
    color: #555;
}
.tarjeta-body {
    display: grid;
    gap: 6px;
    font-size: 14px;
}
.tarjeta-footer {
    margin-top: 14px;
    color: #00695c;
    font-weight: bold;
}
.sin-registros {
    background: #fafafa;
    border: 1px dashed #ccc;
    border-radius: 8px;
    padding: 18px;
    color: #666;
}
</style>

<?php include "views/footer.php"; ?>
