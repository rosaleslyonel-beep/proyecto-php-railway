function guardarMuestra() {
    document.getElementById('form_muestra').submit();
}

function nuevaMuestra() {
    document.getElementById('form_muestra').reset();
    document.getElementById('id_muestra').value = '';
     mostrarCamposDinamicos();
    const tbody = document.getElementById("cuerpo_analisis_muestra");
         tbody.innerHTML = "";
}

function refrescarMuestras() {
    const idProtocolo = document.querySelector('input[name="id_protocolo"]').value;
    location.href = "gestion_protocolos.php?id=" + idProtocolo + "&tab=tab_muestras";
    cargarAnalisisDeMuestra(m.id_muestra);
}

function seleccionarMuestra222(m) {
    document.getElementById('id_muestra').value = m.id_muestra;
    document.getElementById('tipo_muestra').value = m.tipo_muestra;
    document.getElementById('lote').value = m.lote;
    document.getElementById('cantidad').value = m.cantidad;
    document.getElementById('edad').value = m.edad;
    document.getElementById('variedad').value = m.variedad; 

    // Vacunas
    document.getElementById('tipo_vacuna').value = m.tipo_vacuna || '';
    document.getElementById('marca_vacuna').value = m.marca_vacuna || '';
    document.getElementById('dosis').value = m.dosis || '';
    document.getElementById('fecha_elaboracion').value = m.fecha_elaboracion || '';
    document.getElementById('fecha_vencimiento').value = m.fecha_vencimiento || '';

    // Camarones checkboxes
    ['wssv','tsv','ihhnv','imnv','yhv','mrnv','pvnv','ahpnd_ems','ehp','nhpb','div1'].forEach(campo => {
        const checkbox = document.querySelector(`input[name="${campo}"]`);
        if (checkbox) checkbox.checked = m[campo] == 1 ? true : false;
    });

   mostrarCamposDinamicos();
}
function seleccionarMuestra(m) {
    document.getElementById('id_muestra').value = m.id_muestra;
    document.getElementById('tipo_muestra').value = m.tipo_muestra;
    document.getElementById('lote').value = m.lote;
    document.getElementById('cantidad').value = m.cantidad;
    document.getElementById('edad').value = m.edad;
    document.getElementById('variedad').value = m.variedad; 

    // Vacunas
    document.getElementById('tipo_vacuna').value = m.tipo_vacuna || '';
    document.getElementById('marca_vacuna').value = m.marca_vacuna || '';
    document.getElementById('dosis').value = m.dosis || '';
    document.getElementById('fecha_elaboracion').value = m.fecha_elaboracion || '';
    document.getElementById('fecha_vencimiento').value = m.fecha_vencimiento || '';

    // Camarones
    ['wssv','tsv','ihhnv','imnv','yhv','mrnv','pvnv','ahpnd_ems','ehp','nhpb','div1'].forEach(campo => {
        const checkbox = document.querySelector(`input[name="${campo}"]`);
        if (checkbox) checkbox.checked = m[campo] == 1 ? true : false;
    });

    mostrarCamposDinamicos();

    // Marcar la fila seleccionada
    marcarFilaSeleccionada(m.id_muestra);

    // Habilitar botón eliminar
    document.getElementById('btnEliminar').disabled = false;
    cargarAnalisisDeMuestra(m.id_muestra);
}

function mostrarCamposDinamicos() {
    const tipoProtocolo = document.getElementById('tipo_protocolo_hidden') 
        ? document.getElementById('tipo_protocolo_hidden').value.toLowerCase()
        : "";

    if(document.getElementById('campos_vacunas'))
        document.getElementById('campos_vacunas').style.display = tipoProtocolo.includes('vacuna') ? 'block' : 'none';

    if(document.getElementById('campos_camarones'))
        document.getElementById('campos_camarones').style.display = tipoProtocolo.includes('camaron') ? 'block' : 'none';
}

/*function filtrarTabla(col) {
    const input = document.querySelectorAll("#tabla_muestras thead tr:first-child input")[col];
    const filtro = input.value.toUpperCase();
    const filas = document.querySelectorAll("#tabla_muestras tbody tr");

    filas.forEach(f => {
        const celda = f.getElementsByTagName("td")[col];
        f.style.display = celda && celda.textContent.toUpperCase().includes(filtro) ? '' : 'none';
    });
}
*/
function filtrarTabla() {
    const filtros = [];
    const inputs = document.querySelectorAll("#tabla_muestras thead tr:first-child input");

    // Recoger todos los filtros activos
    inputs.forEach(input => {
        filtros.push(input.value.trim().toUpperCase());
    });

    const filas = document.querySelectorAll("#tabla_muestras tbody tr");

    filas.forEach(fila => {
        let mostrar = true;
        const celdas = fila.getElementsByTagName("td");

        filtros.forEach((filtro, index) => {
            if (filtro && celdas[index]) {
                const contenido = celdas[index].textContent.trim().toUpperCase();
                if (!contenido.includes(filtro)) {
                    mostrar = false;
                }
            }
        });

        fila.style.display = mostrar ? "" : "none";
    });
}


// Activar campos dinámicos al cargar
document.addEventListener('DOMContentLoaded', function(){
     mostrarCamposDinamicos();
});

function marcarFilaSeleccionada(idMuestra) {
    document.querySelectorAll("#tabla_muestras tbody tr").forEach(tr => {
        tr.classList.remove('fila-seleccionada');
        // Buscar si este tr tiene el id de muestra correspondiente
        if (tr.getAttribute('data-id-muestra') == idMuestra) {
            tr.classList.add('fila-seleccionada');
        }
    });
}

function eliminarMuestra() {
    const idMuestra = document.getElementById('id_muestra').value;
    const idProtocolo = document.querySelector('input[name="id_protocolo"]').value;

    if (!idMuestra) {
        alert("No hay ninguna muestra seleccionada.");
        return;
    }

    if (confirm("¿Está seguro que desea eliminar la muestra seleccionada?")) {
        window.location.href = "controllers/eliminar_muestra.php?id_muestra=" + idMuestra + "&id_protocolo=" + idProtocolo;
    }
}
