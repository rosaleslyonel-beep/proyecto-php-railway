<div id="tab_reactivos" class="tab-content" style="display:none;">
    <div style="height: 200px; overflow-y: auto; overflow-x: auto; border: 1px solid #ccc; background:#fff;">
        <table width="100%" border="1" cellspacing="0" cellpadding="4" style="border-collapse: collapse;">
            <thead style="position: sticky; top: 0; background: #f1f1f1; z-index: 1;">
                <tr>
                    <th>Orden</th>
                    <th>Reactivo</th>
                    <th>Volumen</th>
                    <th>Unidad</th>
                </tr>
            </thead>
            <tbody id="cuerpo_reactivos"></tbody>
        </table>
    </div>

    <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; padding:10px; background:#f7f7f7; border:1px solid #ccc;">
        <button type="button" onclick="guardarReactivo()">💾 Guardar</button>
        <button type="button" onclick="nuevoReactivo()">➕ Nuevo</button>
        <button type="button" onclick="eliminarReactivo()">🗑 Eliminar</button>
    </div>

    <div style="margin-top:15px; max-height: 280px; overflow-y: auto; border-top:1px solid #ddd; padding-top:12px;">
        <input type="hidden" id="id_reactivo">

        <div class="form-group">
            <label>Orden:</label>
            <input type="number" id="orden_pipeteo">
        </div>

        <div class="form-group">
            <label>Reactivo:</label>
            <textarea id="reactivo" maxlength="200"></textarea>
        </div>

        <div class="form-group">
            <label>Volumen:</label>
            <input type="number" step="0.01" id="volumen">
        </div>

        <div class="form-group">
            <label>Unidad:</label>
            <select id="unidad_medida">
                <option value="µL">µL</option>
                <option value="mL">mL</option>
                <option value="L">L</option>
            </select>
        </div>
    </div>
</div>

<script>
function cargarReactivos(){
fetch('controllers/reactivo_guardar.php?listar=1&id_analisis='+ID_ANALISIS)
.then(r=>r.json())
.then(data=>{
let tbody=document.getElementById('cuerpo_reactivos');
tbody.innerHTML='';
data.forEach(r=>{
let tr=document.createElement('tr');
tr.onclick=()=>seleccionarReactivo(r);
tr.style.cursor='pointer';
tr.innerHTML=`<td>${r.orden_pipeteo}</td><td>${r.reactivo}</td><td>${r.volumen||''}</td><td>${r.unidad_medida}</td>`;
tbody.appendChild(tr);
});
});
}

function seleccionarReactivo(r){
document.getElementById('id_reactivo').value=r.id_reactivo;
document.getElementById('orden_pipeteo').value=r.orden_pipeteo;
document.getElementById('reactivo').value=r.reactivo;
document.getElementById('volumen').value=r.volumen;
document.getElementById('unidad_medida').value=r.unidad_medida;
}

function nuevoReactivo(){
document.getElementById('id_reactivo').value='';
document.getElementById('orden_pipeteo').value='';
document.getElementById('reactivo').value='';
document.getElementById('volumen').value='';
document.getElementById('unidad_medida').value='µL';
}

function guardarReactivo(){
fetch('controllers/reactivo_guardar.php',{
method:'POST',
body:new URLSearchParams({
id_reactivo:document.getElementById('id_reactivo').value,
id_analisis:ID_ANALISIS,
orden_pipeteo:document.getElementById('orden_pipeteo').value,
reactivo:document.getElementById('reactivo').value,
volumen:document.getElementById('volumen').value,
unidad_medida:document.getElementById('unidad_medida').value
})
})
.then(r => r.text())
.then(txt => {
    if ((txt || '').trim() !== 'ok') {
        alert(txt || 'No se pudo guardar el reactivo.');
        return;
    }
    cargarReactivos();
    nuevoReactivo();
})
.catch(err => {
    alert('Error al guardar reactivo.');
    console.error(err);
});
}

function eliminarReactivo(){
let id=document.getElementById('id_reactivo').value;
if(!id)return;
if(!confirm('¿Eliminar?'))return;

fetch('controllers/reactivo_guardar.php',{
method:'POST',
body:new URLSearchParams({eliminar:1,id_reactivo:id})
})
.then(r => r.text())
.then(txt => {
    if ((txt || '').trim() !== 'ok') {
        alert(txt || 'No se pudo eliminar el reactivo.');
        return;
    }
    cargarReactivos();
    nuevoReactivo();
})
.catch(err => {
    alert('Error al eliminar reactivo.');
    console.error(err);
});
}
</script>