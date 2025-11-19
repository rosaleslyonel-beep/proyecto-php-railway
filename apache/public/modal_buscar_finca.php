<div id="modalFinca" style="display:none; position:fixed; top:10%; left:10%; width:80%; height:80%; background:#fff; border:2px solid #004d40; padding:20px; overflow:auto; z-index:1000;">
    <h3>Buscar Unidad Productiva</h3>
    <input type="text" id="busquedaFinca" placeholder="Buscar finca" oninput="buscarFincas()" style="width:100%; margin-bottom:10px;">
    <table border="1" width="100%" cellpadding="5" id="tablaFincas">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Acción</th></tr>
        </thead>
        <tbody>
            <!-- Resultados aquí -->
        </tbody>
    </table>
    <button onclick="cerrarModalFinca()">Cerrar</button>
</div>
