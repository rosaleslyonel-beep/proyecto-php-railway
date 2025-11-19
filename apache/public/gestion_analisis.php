<?php
session_start();
require_once "config/helpers.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
 
$rol = $_SESSION['usuario']['rol_nombre'];
$id_cliente_sesion = $_SESSION['usuario']['id_cliente'] ?? null;

$id_analisis  = $_GET['id'] ?? null;
$analisis = null;
$muestras = [];


if ($id_analisis) {
    $stmt = $conexion->prepare("SELECT p.*  FROM analisis_laboratorio p    WHERE id_analisis = :id");
    $stmt->execute([':id' => $id_analisis]);
    $analisis = $stmt->fetch();
 
}


include "views/header.php";
include "views/menu.php";
?>

<div id="main-content" class="main-content">
    <!-- Panel izquierdo: buscador + lista -->
    <div id="panel-lista" class="panel-lista">
        <div class="panel-lista-header">                       
            <h3>Análisis</h3>
            <input type="text" id="buscador" placeholder="Buscar Análisis..." style="width: 100%; margin-bottom: 10px;">
            <ul id="lista-analisis" style="list-style: none; padding: 0; height: 70vh; overflow-y: auto; border: 1px solid #ccc;"></ul>
        </div>         
    </div>

    <div class="panel-separador" id="panel-separador">
        <button id="togglePanelBtn" title="Ocultar/Motrar lista">&#8592;</button>
    </div>

    <!-- Panel derecho: formulario -->
     <div id="panel-detalle" class="panel-detalle">
       
        <!-- Barra herramientas -->
        <div id="barra-herramientas">
             <div style="display: inline-block;">
                <button type="button" onclick="document.getElementById('form_analisis').submit()" 
                        style="padding: 8px 15px; background-color:rgb(85, 88, 86); color: white; border: none; min-width: 120px;">
                    💾 Guardar
                </button>
            </div>

            <?php if ($analisis): ?>
                <a href="gestion_analisis.php?id=<?= $analisis['id_analisis'] ?>" 
                style="padding: 8px 15px; background-color: rgb(85, 88, 86); color: white; text-decoration: none; min-width: 120px; text-align: center;">
                    🔄 Refrescar
                </a>
            <?php endif; ?>
            

            <a href="gestion_analisis.php" 
            style="padding: 8px 15px; background-color: rgb(85, 88, 86); color: white; text-decoration: none; min-width: 120px; text-align: center;">
                ➕ Nuevo
            </a>
              <?php if ($analisis): ?>
                <button type="button" onclick="eliminarAnalisis(<?= $analisis['id_analisis'] ?>)" style="background:#e53935;color:#fff;margin-left:10px;">Eliminar</button>
            <?php endif; ?>
        </div>
        <div class="tabs">
            <ul style="list-style:none; display:flex; gap:1px; padding:8px; border-bottom:1px solid #ccc;">
                <li><a href="#" onclick="mostrarTab('tab_datos')" class="tablink activo">📄 Datos del Análisis</a></li>
                
                 <?php if ($id_analisis): ?>
                    <li><a href="#" onclick="mostrarTab('tab_roles')" class="tablink">🔐 Asignar Roles</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <form id="form_analisis" action="controllers/analisis_guardar.php" method="POST" autocomplete="off">
            <div id="contenido-pestanas" >
                <div id="tab_datos" class="tab-content" style="display:block;">
                    <h2><?= $id_analisis  ? 'Editar' : 'Registrar' ?> Análisis de Laboratorio</h2>
                
                    <input type="hidden" name="id_analisis" value="<?= htmlspecialchars($analisis['id_analisis'] ?? '') ?>">
                    <div class="form-group">
                        <label>Nombre del Estudio:</label>
                        <input type="text" name="nombre_estudio" required maxlength="100" value="<?= htmlspecialchars($analisis['nombre_estudio'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Descripción:</label>
                        <textarea name="descripcion"><?= htmlspecialchars($analisis['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Precio (Q):</label>
                        <input type="number" name="precio" min="0" step="0.01" required value="<?= htmlspecialchars($analisis['precio'] ?? '') ?>">
                    
                    <label>Tipo de Formulario para Resultado:</label>
                    <select name="tipo_formulario" required>
                        <option value="">-- Seleccione --</option>
                        <option value="hi" <?= isset($analisis) && $analisis['tipo_formulario'] === 'hi' ? 'selected' : '' ?>>HI</option>
                        <option value="elisa" <?= isset($analisis) && $analisis['tipo_formulario'] === 'elisa' ? 'selected' : '' ?>>ELISA</option>
                        <option value="IDIA" <?= isset($analisis) && $analisis['tipo_formulario'] === 'IDIA' ? 'selected' : '' ?>>IDIA</option>
                        <option value="generico" <?= isset($analisis) && $analisis['tipo_formulario'] === 'generico' ? 'selected' : '' ?>>Genérico</option>
                    </select>
                    </div>
                </div>
                <div id="tab_roles" class="tab-content" style="display:none; margin-top: 15px;">
                    <h4>Roles Permitidos para este Análisis</h4>

                    <!-- Tabla de roles asignados -->
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc;">
                        <table id="tabla_roles_asignados" width="100%" border="1" cellspacing="0" cellpadding="4">
                            <thead>
                                <tr>
                                    <th>Rol</th>
                                    <th>Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo_roles_asignados">
                                <?php
                                $stmt = $conexion->prepare("SELECT r.id_rol, r.nombre_rol FROM analisis_roles ar JOIN roles r ON ar.id_rol = r.id_rol WHERE ar.id_analisis = ?");
                                $stmt->execute([$id_analisis]);
                                foreach ($stmt->fetchAll() as $rol) {
                                    echo "<tr id='rol-row-{$rol['id_rol']}'>
                                            <td>{$rol['nombre_rol']}</td>
                                            <td><button type='button' onclick='quitarRol({$rol['id_rol']})'>❌</button></td>
                                            <input type='hidden' name='roles[]' value='{$rol['id_rol']}'>
                                        </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Formulario para agregar rol -->
                    <div style="margin-top: 10px;">
                        <label for="nuevo_rol">Agregar Rol:</label>
                        <select id="nuevo_rol">
                            <option value="">-- Seleccione un rol --</option>
                            <?php
                            $todosRoles = $conexion->query("SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol")->fetchAll();
                            foreach ($todosRoles as $rol) {
                                echo "<option value='{$rol['id_rol']}'>{$rol['nombre_rol']}</option>";
                            }
                            ?>
                        </select>
                        <button type="button" onclick="agregarRol()">➕ Agregar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

</div>

<!-- Estilos inline rápidos para el ejemplo -->
<style>
    #main-content {
        display: flex;
        height: calc(100vh - 60px);
    }

   

    #barra-herramientas {
        background-color: #263238;
        color: white;
        padding: 10px;
        position: sticky;
        top: 0;
        z-index: 10;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    #barra-herramientas button,
    #barra-herramientas a {
        padding: 8px 15px;
        background-color: #263238;
        color: white;
        text-decoration: none;
        border: none;
        cursor: pointer;
        min-width: 100px;
        text-align: center;
    }

   

    #panel-lista ul {
        list-style: none;
        padding: 0;
    }

    #panel-lista li {
        padding: 5px 10px;
        border-bottom: 1px solid #ccc;
        cursor: pointer;
    }

    #panel-lista li:hover {
        background-color: #ddd;
    }


        /* Contenedor de tabs */
    .tablink {
        text-decoration: none;
        padding: 8px 1px;
        border: 4px solid #ccc;
        border-bottom: none; /* Quitar la línea inferior */
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        background-color: #f1f1f1;
        color: #004d40;
        position: relative;
        top: 1px;
        overflow: hidden;
    }

    .tablink.activo {
        background-color: #e0f2f1;
        font-weight: bold;
        border-color: #004d40 #004d40 white #004d40; /* no hay borde abajo */
    }

    .tablink:hover {
        background-color: #c8e6c9;
        color: #004d40;
    }

</style>

<script>
function agregarRol() {
    const select = document.getElementById("nuevo_rol");
    const id_rol = select.value;
    const nombre = select.options[select.selectedIndex].text;

    if (!id_rol || document.getElementById('rol-row-' + id_rol)) return;

    const row = document.createElement("tr");
    row.id = "rol-row-" + id_rol;
    row.innerHTML = `
        <td>${nombre}</td>
        <td><button type="button" onclick="quitarRol(${id_rol})">❌</button></td>
        <input type="hidden" name="roles[]" value="${id_rol}">
    `;
    document.getElementById("cuerpo_roles_asignados").appendChild(row);
    select.value = "";
}

function quitarRol(id) {
    const row = document.getElementById("rol-row-" + id);
    if (row) row.remove();
}



  const panel = document.getElementById('panel-lista');
const btn = document.getElementById('togglePanelBtn');
let panelOculto = false;

btn.addEventListener('click', () => {
    panelOculto = !panelOculto;
    panel.classList.toggle('oculto', panelOculto);
    btn.innerHTML = panelOculto ? '&#8594;' : '&#8592;'; // Flecha derecha/izquierda
    // Si deseas guardar el estado, usa localStorage aquí
});
function mostrarTab(tabId) {
    // Oculta todos los contenidos de pestañas
    document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
    // Quita clase activo a todos los enlaces
    document.querySelectorAll('.tablink').forEach(link => link.classList.remove('activo'));
    // Muestra el contenido del tab seleccionado
    document.getElementById(tabId).style.display = 'block';
    // Agrega activo al tab seleccionado
    const tabs = document.querySelectorAll('.tablink');
    tabs.forEach(link => {
        if (link.getAttribute('onclick').includes(tabId)) {
            link.classList.add('activo');
        }
    });
}

// Scroll infinito de analisis
let pagina = 1;
let terminoBusqueda = '';
let cargando = false;

function cargaranalisis(reset = false) {
    if (cargando) return;
    cargando = true;

    const lista = document.getElementById('lista-analisis');
    fetch(`controllers/buscar_analisis.php?busqueda=${encodeURIComponent(terminoBusqueda)}&pagina=${pagina}`)
        .then(res => res.json())
        .then(analisis => {
            if (reset) lista.innerHTML = '';
            if (analisis.length === 0 && pagina === 1) {
                lista.innerHTML = '<li>🔍 No se encontraron Analisis.</li>';
            } else {
                const actual = new URLSearchParams(window.location.search).get("id");
                analisis.forEach(p => {
                    const li = document.createElement('li');
                    li.className = "cliente-item" + (p.id_analisis == actual ? " activo" : "");
                    li.innerHTML = `<a href="gestion_analisis.php?id=${p.id_analisis}">${p.id_analisis} -${p.nombre_estudio}</a>`;
                    lista.appendChild(li);
                });
            }
            cargando = false;
        });
}

document.getElementById('buscador').addEventListener('input', () => {
    terminoBusqueda = document.getElementById('buscador').value;
    pagina = 1;
    cargaranalisis(true);
});

document.getElementById('lista-analisis').addEventListener('scroll', () => {
    const lista = document.getElementById('lista-analisis');
    if (lista.scrollTop + lista.clientHeight >= lista.scrollHeight - 10) {
        pagina++;
        cargaranalisis();
    }
});

 cargaranalisis();
 
// Filtrar análisis (puede mejorarse con AJAX para grandes catálogos)
function filtrarAnalisis() {
    const val = document.getElementById('buscador-analisis').value;
    window.location = 'gestion_analisis.php?busqueda=' + encodeURIComponent(val);
}

// Eliminar análisis
function eliminarAnalisis(id) {
    if (!confirm("¿Está seguro de eliminar este análisis?")) return;
    fetch('controllers/analisis_guardar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_analisis=' + encodeURIComponent(id) + '&eliminar=1'
    })
    .then(resp => resp.text())
    .then(txt => {
        alert("Análisis eliminado.");
        window.location = 'gestion_analisis.php';
    });
}
</script>

<?php include "views/footer.php"; ?>
