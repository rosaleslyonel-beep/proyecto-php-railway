<?php
require_once __DIR__ . "/../config/helpers.php";
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>

<!-- Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<style>
    /* ===== MENÚ COLAPSABLE ===== */

    /* Sidebar normal */
    #sidebar {
        height: 100vh;
        width: 230px; /* ancho inicial */
        background-color: #263238;
        color: white;
        display: flex;
        flex-direction: column;
        padding-top: 10px;
        position: fixed;
        top: 0;
        left: 0;
        overflow-y: auto;
        transition: width 0.3s;
    }

    /* Enlaces */
    #sidebar a {
        color: white;
        padding: 12px 20px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        border-radius: 4px;
        transition: background-color 0.3s;
        white-space: nowrap;
       
    }

    #sidebar a:hover {
        background-color: #37474f;
    }

    #sidebar a.active {
        background-color: #00695c;
    }

    #sidebar h4 {
        color: #b0bec5;
        margin: 10px 20px 5px;
        font-size: 13px;
        text-transform: uppercase;
    }

    #sidebar .material-icons {
        font-size: 18px;
    }

    /* Botón hamburguesa */
    #toggle-btn {
        background-color: #37474f;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        font-size: 20px;
        text-align: center;        
    }

    #toggle-btn:hover {
        background-color: #37474f;
    }

    /* ===== CONTENIDO ===== */
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        margin-left: 230px;
        transition: margin-left 0.3s;
    }

    /* ===== MENÚ EXPANDIDO ===== */
    #sidebar.open {
        width: 240px; /* si expandes */
    }

    /* ===== MENÚ COLAPSADO ===== */
    body.collapsed {
        margin-left: 60px;
    }

    body.collapsed #sidebar {
        width: 60px;
    }

    body.collapsed #sidebar a span.text,
    body.collapsed #sidebar h4 {
        display: none;
    }

    /* Tooltip en modo colapsado */
    body.collapsed #sidebar a {
        position: relative;
    }

    body.collapsed #sidebar a:hover::after {
        content: attr(title);
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        background-color: #37474f;
        color: #fff;
        padding: 5px 10px;
        border-radius: 4px;
        white-space: nowrap;
        margin-left: 5px;
        z-index: 1000;
        font-size: 12px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {

        body {
            margin-left: 0;
            transition: margin-left 0.3s;
        }

        #sidebar {
            position: fixed;
            left: -230px; /* oculto por defecto */
            width: 230px;
            transition: left 0.3s;
        }

        /* Mostrar el sidebar cuando se abre */
        body.sidebar-open #sidebar {
            left: 0;
            width: 260px;
        }

        /* Evitar margen en colapsado para móviles */
        body.collapsed {
            margin-left: 0;
        }
        body.collapsed #sidebar a span.text,
    body.collapsed #sidebar h4 {
        display: flex;
    }


    
    }

    /* Para dispositivos grandes: mantener arriba */
@media screen and (min-width: 769px) {
    #toggleButton {
        position: relative;
        margin-top: 10px;
    }
}

/* Para móviles: mover a la parte inferior */
@media screen and (max-width: 768px) {
    #toggleButton {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
    }
}

</style>
<!-- Botón hamburguesa para móvil -->
<div id="toggle-btn" onclick="toggleMenu()"></div>
<div id="sidebar" class="sidebar">

    <button id="toggle-btn" onclick="toggleSidebar()">
        <span id="toggle-icon">☰</span>
    </button>

    <a href="dashboard.php" title= "Inicio" class="<?= ($pagina_actual == 'dashboard.php') ? 'active' : '' ?>">
        <span class="material-icons">home</span> <span class="text">Inicio</span>
    </a>   

    <h4>Protocolos</h4>
    <?php if (verificarPermiso('10', 'consultar')): ?>
        <a href="gestion_protocolos.php" title= "Protocolos" class="<?= ($pagina_actual == 'gestion_protocolos.php') ? 'active' : '' ?>">
            <span class="material-icons">assignment</span> <span class="text">Protocolos</span>
        </a>
    <?php endif; ?>

    <h4>Administración</h4>
    <?php if (verificarPermiso('5', 'consultar')): ?>
        <a href="gestion_usuarios.php" title= "Usuarios" class="<?= ($pagina_actual == 'gestion_usuarios.php') ? 'active' : '' ?>">
            <span class="material-icons">people</span> <span class="text">Usuarios</span>
        </a>
    <?php endif; ?>
    <?php if (verificarPermiso('15', 'consultar')): ?>
        <a href="gestion_analisis.php" title= "Catálogo de Análisis" class="<?= ($pagina_actual == 'gestion_analisis.php') ? 'active' : '' ?>">
            <span class="material-icons">science</span> <span class="text">Prueba de Laboratorio</span>
        </a>
    <?php endif; ?>
    <?php if (verificarPermiso('5', 'consultar')): ?>
        <a href="gestion_roles.php" title= "Roles" class="<?= ($pagina_actual == 'gestion_roles.php') ? 'active' : '' ?>">
            <span class="material-icons">security</span> <span class="text">Roles</span>
        </a>
    <?php endif; ?>
    <?php if (verificarPermiso('9', 'consultar')): ?>
        <a href="gestion_pantallas.php" title= "Pantallas" class="<?= ($pagina_actual == 'gestion_pantallas.php') ? 'active' : '' ?>">
            <span class="material-icons">view_module</span> <span class="text">Pantallas</span>
        </a>
    <?php endif; ?>
    <?php if (verificarPermiso('5', 'consultar')): ?>
        <a href="gestion_clientes.php" title= "Clientes" class="<?= ($pagina_actual == 'gestion_clientes.php') ? 'active' : '' ?>">
            <span class="material-icons">business</span> <span class="text">Clientes</span>
        </a>
    <?php endif; ?>
    <?php if (verificarPermiso('8', 'consultar')): ?>
        <a href="gestion_fincas.php" title= "Fincas" class="<?= ($pagina_actual == 'gestion_fincas.php') ? 'active' : '' ?>">
            <span class="material-icons">agriculture</span> <span class="text">Fincas</span>
        </a>
    <?php endif; ?>
    <?php if (verificarPermiso('5', 'consultar')): ?>
        <a href="gestion_tipos_protocolo.php" title= "Tipos de Protocolo" class="<?= ($pagina_actual == 'gestion_tipos_protocolo.php') ? 'active' : '' ?>">
            <span class="material-icons">category</span> <span class="text">Tipos de Protocolo</span>
        </a>
    <?php endif; ?>

    <h4>Cuenta</h4>
    <a href="logout.php">
        <span class="material-icons">exit_to_app</span> <span class="text">Cerrar Sesión</span>
    </a>

</div>

<script>
// Al cargar, revisa si estaba colapsado y aplica
if (localStorage.getItem('menuColapsado') === '1') {
    document.body.classList.add('collapsed');
    document.getElementById('toggle-icon').textContent = '☰';
}

function toggleSidebar() {
    if (window.innerWidth <= 768) {
        // Si es móvil, abre/cierra
        document.body.classList.toggle('sidebar-open');
    } else {
        document.body.classList.toggle('collapsed');

        // Guarda estado en localStorage
        if (document.body.classList.contains('collapsed')) {
            localStorage.setItem('menuColapsado', '1');
            document.getElementById('toggle-icon').textContent = '☰';
        } else {
            localStorage.setItem('menuColapsado', '0');
            document.getElementById('toggle-icon').textContent = '✖';
        }
    }
}

// Si está expandido al cargar, muestra la X
if (!document.body.classList.contains('collapsed') && window.innerWidth > 768) {
    document.getElementById('toggle-icon').textContent = '✖';
}
</script>
<script>
function toggleMenu() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}



</script>
<script>
    // Detectar el botón
    const toggleBtn = document.getElementById('toggle-btn');

    // Cuando hagan clic en el botón
    toggleBtn.addEventListener('click', () => {

        // Para móviles (ancho <= 768px)
        if (window.innerWidth <= 768) {
            document.body.classList.toggle('sidebar-open');
        } else {
            // Para escritorio: alternar colapsado/expandido
            document.body.classList.toggle('collapsed');
        }
    });

    // (Opcional) Si haces resize de pantalla, asegúrate que el menú se comporta bien
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            // En escritorio quitamos la clase sidebar-open si estaba activa
            document.body.classList.remove('sidebar-open');
        }
    });
</script>
