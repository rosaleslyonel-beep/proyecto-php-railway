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

function obtenerRegistros(PDO $conexion, string $sql, array $params = []): array {
    $stmt = $conexion->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildQuickRangeUrl(int $days, string $estadoFiltro): string {
    $params = [
        'fecha_desde' => date('Y-m-d', strtotime("-$days days")),
        'fecha_hasta' => date('Y-m-d')
    ];
    if ($estadoFiltro !== '') {
        $params['estado'] = $estadoFiltro;
    }
    return 'dashboard.php?' . http_build_query($params);
}

function buildTodayUrl(string $estadoFiltro): string {
    $params = [
        'fecha_desde' => date('Y-m-d'),
        'fecha_hasta' => date('Y-m-d')
    ];
    if ($estadoFiltro !== '') {
        $params['estado'] = $estadoFiltro;
    }
    return 'dashboard.php?' . http_build_query($params);
}

function filtroFechas(array &$params, string $fechaDesde, string $fechaHasta): string {
    $sql = '';
    if ($fechaDesde !== '') {
        $params[':fecha_desde'] = $fechaDesde;
        $sql .= ' AND DATE(p.created_date) >= :fecha_desde';
    }
    if ($fechaHasta !== '') {
        $params[':fecha_hasta'] = $fechaHasta;
        $sql .= ' AND DATE(p.created_date) <= :fecha_hasta';
    }
    return $sql;
}

function filtroEstado(array &$params, string $estadoFiltro): string {
    if ($estadoFiltro === '') {
        return '';
    }
    $params[':estado_filtro'] = $estadoFiltro;
    return ' AND p.estado = :estado_filtro';
}

function renderCards(array $rows, bool $abrirResultados = false, bool $showAnalisis = false): void {
    if (empty($rows)) {
        echo '<div class="empty-state">No hay protocolos en este rango.</div>';
        return;
    }

    foreach ($rows as $row) {
        $url = 'gestion_protocolos.php?id=' . (int)$row['id_protocolo'];
        if ($abrirResultados) {
            $url .= '&tab=tab_resultados';
        }

        $estado = $row['estado'] ?? 'BORRADOR';
        $estadoClass = strtolower(str_replace(' ', '-', $estado));
        $correlativo = trim((string)($row['correlativo'] ?? ''));
        $correlativo = $correlativo !== '' ? $correlativo : 'Sin correlativo';

        echo '<a class="protocol-card" href="' . htmlspecialchars($url) . '">';
        echo '  <div class="protocol-card__top">';
        echo '      <span class="badge badge--' . htmlspecialchars($estadoClass) . '">' . htmlspecialchars($estado) . '</span>';
        echo '      <span class="protocol-card__code">' . htmlspecialchars($correlativo) . '</span>';
        echo '  </div>';
        echo '  <div class="protocol-card__title">Protocolo #' . (int)$row['id_protocolo'] . '</div>';
        echo '  <div class="protocol-card__meta-grid">';
        echo '      <div><span>Fecha</span><strong>' . htmlspecialchars($row['fecha'] ?? '—') . '</strong></div>';
        echo '      <div><span>Muestras</span><strong>' . (int)($row['total_muestras'] ?? 0) . '</strong></div>';
        echo '      <div class="full"><span>Cliente</span><strong>' . htmlspecialchars($row['cliente'] ?? '—') . '</strong></div>';
        echo '      <div><span>Finca</span><strong>' . htmlspecialchars(($row['finca'] ?? '') !== '' ? $row['finca'] : '—') . '</strong></div>';
        echo '      <div><span>Tipo</span><strong>' . htmlspecialchars(($row['tipo_protocolo'] ?? '') !== '' ? $row['tipo_protocolo'] : '—') . '</strong></div>';
        if ($showAnalisis) {
            echo '  <div class="full"><span>Análisis asignados</span><strong>' . (int)($row['total_analisis'] ?? 0) . '</strong></div>';
        }
        echo '  </div>';
        echo '  <div class="protocol-card__footer">Abrir protocolo <span>→</span></div>';
        echo '</a>';
    }
}

$paramsBase = [];
$whereFechas = filtroFechas($paramsBase, $fechaDesde, $fechaHasta);
$whereEstado = filtroEstado($paramsBase, $estadoFiltro);

$selectBase = "
    SELECT
        p.id_protocolo,
        p.correlativo,
        COALESCE(p.estado, 'BORRADOR') AS estado,
        TO_CHAR(COALESCE(p.fecha, p.created_date), 'YYYY-MM-DD') AS fecha,
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
$groupOrder = "
    GROUP BY p.id_protocolo, p.correlativo, p.estado, COALESCE(p.fecha, p.created_date), c.nombre, f.nombre_finca, tp.nombre_tipo
    ORDER BY COALESCE(p.fecha, p.created_date) DESC, p.id_protocolo DESC
";

$sections = [];
$summary = [];
$pageTitle = 'Dashboard';

if ($rolNombre === 'cliente') {
    $params = $paramsBase;
    $params[':id_cliente'] = $idClienteSesion;

    $sqlProceso = $selectBase . "
        WHERE p.id_cliente = :id_cliente
          AND COALESCE(p.estado, 'BORRADOR') IN ('BORRADOR', 'PENDIENTE_RESULTADOS')
          $whereFechas
          $whereEstado
    " . $groupOrder;

    $sqlFinalizados = $selectBase . "
        WHERE p.id_cliente = :id_cliente
          AND COALESCE(p.estado, 'BORRADOR') = 'CERRADO'
          $whereFechas
          $whereEstado
    " . $groupOrder;

    $proceso = obtenerRegistros($conexion, $sqlProceso, $params);
    $finalizados = obtenerRegistros($conexion, $sqlFinalizados, $params);

    $summary = [
        ['label' => 'En proceso', 'value' => count($proceso), 'tone' => 'amber'],
        ['label' => 'Finalizados', 'value' => count($finalizados), 'tone' => 'green'],
        ['label' => 'Rango', 'value' => htmlspecialchars($fechaDesde) . ' a ' . htmlspecialchars($fechaHasta), 'tone' => 'slate', 'isText' => true],
    ];

    $sections = [
        ['title' => 'Protocolos en proceso', 'rows' => $proceso, 'results' => false, 'analisis' => false],
        ['title' => 'Protocolos finalizados', 'rows' => $finalizados, 'results' => true, 'analisis' => false],
    ];
} elseif (in_array($rolNombre, ['admin', 'administrador', 'recepcion'], true)) {
    $sqlBorrador = $selectBase . "
        WHERE COALESCE(p.estado, 'BORRADOR') = 'BORRADOR'
          $whereFechas
          $whereEstado
    " . $groupOrder;

    $sqlPendientes = $selectBase . "
        WHERE COALESCE(p.estado, 'BORRADOR') = 'PENDIENTE_RESULTADOS'
          $whereFechas
          $whereEstado
    " . $groupOrder;

    $borrador = obtenerRegistros($conexion, $sqlBorrador, $paramsBase);
    $pendientes = obtenerRegistros($conexion, $sqlPendientes, $paramsBase);

    $summary = [
        ['label' => 'Pendientes de ingreso', 'value' => count($borrador), 'tone' => 'amber'],
        ['label' => 'Listos para resultados', 'value' => count($pendientes), 'tone' => 'blue'],
        ['label' => 'Rango', 'value' => htmlspecialchars($fechaDesde) . ' a ' . htmlspecialchars($fechaHasta), 'tone' => 'slate', 'isText' => true],
    ];

    $sections = [
        ['title' => 'Pendientes de ingreso', 'rows' => $borrador, 'results' => false, 'analisis' => false],
        ['title' => 'Listos para resultados', 'rows' => $pendientes, 'results' => true, 'analisis' => false],
    ];
} else {
    $params = $paramsBase;
    $params[':id_rol'] = $idRol;

    $sqlAnalista = "
        SELECT
            p.id_protocolo,
            p.correlativo,
            COALESCE(p.estado, 'BORRADOR') AS estado,
            TO_CHAR(COALESCE(p.fecha, p.created_date), 'YYYY-MM-DD') AS fecha,
            c.nombre AS cliente,
            COALESCE(f.nombre_finca, '') AS finca,
            COALESCE(tp.nombre_tipo, '') AS tipo_protocolo,
            COUNT(DISTINCT m.id_muestra) AS total_muestras,
            COUNT(DISTINCT ma.id_analisis) AS total_analisis
        FROM protocolos p
        INNER JOIN clientes c ON c.id_cliente = p.id_cliente
        LEFT JOIN fincas f ON f.id_finca = p.id_finca
        LEFT JOIN tipos_protocolo tp ON tp.id_tipo_protocolo = p.id_tipo_protocolo
        INNER JOIN muestras m ON m.id_protocolo = p.id_protocolo
        INNER JOIN muestra_analisis ma ON ma.id_muestra = m.id_muestra
        INNER JOIN analisis_roles ar ON ar.id_analisis = ma.id_analisis
        WHERE ar.id_rol = :id_rol
          AND COALESCE(p.estado, 'BORRADOR') = 'PENDIENTE_RESULTADOS'
          $whereFechas
          $whereEstado
        GROUP BY p.id_protocolo, p.correlativo, p.estado, COALESCE(p.fecha, p.created_date), c.nombre, f.nombre_finca, tp.nombre_tipo
        ORDER BY COALESCE(p.fecha, p.created_date) DESC, p.id_protocolo DESC
    ";

    $asignados = obtenerRegistros($conexion, $sqlAnalista, $params);
    $summary = [
        ['label' => 'Asignados para resultados', 'value' => count($asignados), 'tone' => 'blue'],
        ['label' => 'Rango', 'value' => htmlspecialchars($fechaDesde) . ' a ' . htmlspecialchars($fechaHasta), 'tone' => 'slate', 'isText' => true],
    ];
    $sections = [
        ['title' => 'Protocolos asignados', 'rows' => $asignados, 'results' => true, 'analisis' => true],
    ];
}

include "views/header.php";
include "views/menu.php";
?>

<div id="main-content" class="dashboard-shell">
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p>Bienvenido, <?= htmlspecialchars($usuario['nombre'] ?? '') ?> · Rol: <?= htmlspecialchars(ucfirst($usuario['rol_nombre'] ?? '')) ?></p>
        </div>
        <div class="quick-ranges">
            <a href="<?= htmlspecialchars(buildTodayUrl($estadoFiltro)) ?>">Hoy</a>
            <a href="<?= htmlspecialchars(buildQuickRangeUrl(7, $estadoFiltro)) ?>">7 días</a>
            <a href="<?= htmlspecialchars(buildQuickRangeUrl(30, $estadoFiltro)) ?>">30 días</a>
            <a href="<?= htmlspecialchars(buildQuickRangeUrl(90, $estadoFiltro)) ?>">90 días</a>
        </div>
    </div>

    <form method="GET" class="filter-bar">
        <div class="filter-field">
            <label for="fecha_desde">Desde</label>
            <input type="date" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde) ?>">
        </div>
        <div class="filter-field">
            <label for="fecha_hasta">Hasta</label>
            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta) ?>">
        </div>
        <div class="filter-field filter-field--wide">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <option value="BORRADOR" <?= $estadoFiltro === 'BORRADOR' ? 'selected' : '' ?>>Borrador</option>
                <option value="PENDIENTE_RESULTADOS" <?= $estadoFiltro === 'PENDIENTE_RESULTADOS' ? 'selected' : '' ?>>Pendiente resultados</option>
                <option value="CERRADO" <?= $estadoFiltro === 'CERRADO' ? 'selected' : '' ?>>Cerrado</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-primary">Filtrar</button>
            <a href="dashboard.php" class="btn-secondary">Limpiar</a>
        </div>
    </form>

    <div class="summary-grid">
        <?php foreach ($summary as $item): ?>
            <div class="summary-card summary-card--<?= htmlspecialchars($item['tone']) ?>">
                <span><?= htmlspecialchars($item['label']) ?></span>
                <?php if (!empty($item['isText'])): ?>
                    <strong class="summary-text"><?= $item['value'] ?></strong>
                <?php else: ?>
                    <strong><?= (int)$item['value'] ?></strong>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="sections-grid sections-grid--<?= count($sections) > 1 ? 'two' : 'one' ?>">
        <?php foreach ($sections as $section): ?>
            <section class="section-panel">
                <div class="section-panel__head">
                    <div>
                        <h2><?= htmlspecialchars($section['title']) ?></h2>
                        <p><?= count($section['rows']) ?> protocolo(s)</p>
                    </div>
                </div>
                <div class="cards-grid">
                    <?php renderCards($section['rows'], $section['results'], $section['analisis']); ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<style>
#main-content.dashboard-shell {
    display: block !important;
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 24px;
    box-sizing: border-box;
}

.dashboard-shell {
    background: #f5f7fb;
    min-height: calc(100vh - 70px);
}

.page-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.page-head h1 {
    margin: 0 0 6px;
    font-size: 32px;
    color: #17324d;
}

.page-head p {
    margin: 0;
    color: #607086;
}

.quick-ranges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.quick-ranges a {
    text-decoration: none;
    background: #ffffff;
    border: 1px solid #d8e0ea;
    color: #0e5f52;
    padding: 10px 14px;
    border-radius: 999px;
    font-weight: 600;
    transition: all .15s ease;
}

.quick-ranges a:hover {
    background: #0a6c5c;
    color: #fff;
    border-color: #0a6c5c;
}

.filter-bar {
    background: #ffffff;
    border: 1px solid #e5eaf0;
    border-radius: 18px;
    padding: 16px;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    align-items: end;
    box-shadow: 0 8px 24px rgba(22, 42, 66, 0.06);
    margin-bottom: 18px;
}

.filter-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-field label {
    font-size: 13px;
    font-weight: 700;
    color: #42566d;
}

.filter-field input,
.filter-field select {
    height: 42px;
    border: 1px solid #d2dbe6;
    border-radius: 12px;
    padding: 0 12px;
    font-size: 14px;
    background: #fbfdff;
}

.filter-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-primary,
.btn-secondary {
    height: 42px;
    border-radius: 12px;
    padding: 0 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-weight: 700;
}

.btn-primary {
    background: #0a6c5c;
    color: #fff;
}

.btn-secondary {
    background: #e9eef5;
    color: #42566d;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.summary-card {
    background: #fff;
    border-radius: 18px;
    padding: 18px 20px;
    border: 1px solid #e5eaf0;
    box-shadow: 0 8px 24px rgba(22, 42, 66, 0.05);
}

.summary-card span {
    display: block;
    color: #66788f;
    font-size: 13px;
    margin-bottom: 6px;
    font-weight: 700;
}

.summary-card strong {
    display: block;
    font-size: 30px;
    color: #17324d;
}

.summary-card .summary-text {
    font-size: 18px;
    line-height: 1.3;
}

.summary-card--amber { border-left: 6px solid #d19a00; }
.summary-card--blue { border-left: 6px solid #2b7fff; }
.summary-card--green { border-left: 6px solid #1a9c52; }
.summary-card--slate { border-left: 6px solid #6c7b8b; }

.sections-grid {
    display: grid;
    gap: 18px;
}

.sections-grid--two {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.sections-grid--one {
    grid-template-columns: 1fr;
}

.section-panel {
    background: #ffffff;
    border: 1px solid #e5eaf0;
    border-radius: 22px;
    padding: 18px;
    box-shadow: 0 10px 28px rgba(22, 42, 66, 0.06);
}

.section-panel__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.section-panel__head h2 {
    margin: 0 0 4px;
    color: #17324d;
    font-size: 20px;
}

.section-panel__head p {
    margin: 0;
    color: #74859a;
    font-size: 13px;
}

.cards-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
}

.protocol-card {
    text-decoration: none;
    color: inherit;
    border: 1px solid #e7edf3;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    border-radius: 18px;
    padding: 16px;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}

.protocol-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(16, 33, 52, 0.08);
    border-color: #cfd9e5;
}

.protocol-card__top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}

.badge--borrador { background: #fff3d1; color: #9a6a00; }
.badge--pendiente_resultados { background: #dcecff; color: #1559b7; }
.badge--cerrado { background: #ddf7e7; color: #12753d; }

.protocol-card__code {
    font-size: 13px;
    color: #6b7a8d;
    font-weight: 700;
}

.protocol-card__title {
    font-size: 22px;
    font-weight: 800;
    color: #17324d;
    margin-bottom: 14px;
}

.protocol-card__meta-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px 14px;
}

.protocol-card__meta-grid div {
    background: #f7fafc;
    border-radius: 12px;
    padding: 10px 12px;
}

.protocol-card__meta-grid .full {
    grid-column: 1 / -1;
}

.protocol-card__meta-grid span {
    display: block;
    color: #738398;
    font-size: 12px;
    margin-bottom: 5px;
    font-weight: 700;
}

.protocol-card__meta-grid strong {
    color: #21384f;
    font-size: 14px;
}

.protocol-card__footer {
    margin-top: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #0a6c5c;
    font-weight: 800;
}

.empty-state {
    border: 2px dashed #d6dee7;
    background: #fafcfe;
    border-radius: 16px;
    padding: 26px 18px;
    color: #74859a;
    text-align: center;
}

@media (max-width: 1200px) {
    .sections-grid--two,
    .summary-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .filter-bar {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 640px) {
    #main-content.dashboard-shell {
        padding: 16px;
    }

    .filter-bar,
    .summary-grid,
    .sections-grid--two,
    .sections-grid--one,
    .cards-grid {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        justify-content: stretch;
        flex-direction: column;
    }

    .btn-primary,
    .btn-secondary {
        width: 100%;
    }

    .protocol-card__meta-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include "views/footer.php"; ?>
