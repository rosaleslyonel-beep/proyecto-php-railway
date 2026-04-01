<div id="tab_reactivos" class="tab-content" style="display:none;">
    <h3>Reactivos</h3>

    <div style="max-height:250px;overflow-y:auto;border:1px solid #ccc;">
        <table width="100%" border="1">
            <thead>
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

    <div style="margin-top:15px;">
        <input type="hidden" id="id_reactivo">

        <label>Orden:</label>
        <input type="number" id="orden_pipeteo"><br>

        <label>Reactivo:</label>
        <textarea id="reactivo"></textarea><br>

        <label>Volumen:</label>
        <input type="number" step="0.01" id="volumen"><br>

        <label>Unidad:</label>
        <select id="unidad_medida">
            <option value="µL">µL</option>
            <option value="mL">mL</option>
            <option value="L">L</option>
        </select><br><br>

        <button onclick="guardarReactivo()">Guardar</button>
        <button onclick="nuevoReactivo()">Nuevo</button>
        <button onclick="eliminarReactivo()">Eliminar</button>
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
}).then(()=>{cargarReactivos();nuevoReactivo();});
}


function eliminarReactivo(){
let id=document.getElementById('id_reactivo').value;
if(!id)return;
if(!confirm('Eliminar?'))return;

fetch('controllers/reactivo_guardar.php',{
method:'POST',
body:new URLSearchParams({eliminar:1,id_reactivo:id})
}).then(()=>{cargarReactivos();nuevoReactivo();});
}
</script>
