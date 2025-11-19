<?php
require_once "config/helpers.php";
session_start();

$id_protocolo = $_GET['id_protocolo'] ?? null;
if (!$id_protocolo) {
    echo "Protocolo no especificado.";
    exit;
}

// Obtener protocolo
$stmt = $conexion->prepare("
    SELECT p.*, c.nombre nombre_cliente, f.nombre_finca, t.nombre_tipo
    FROM protocolos p
    JOIN clientes c ON p.id_cliente = c.id_cliente
    JOIN fincas f ON p.id_finca = f.id_finca
    JOIN tipos_protocolo t ON p.id_tipo_protocolo = t.id_tipo_protocolo
    WHERE p.id_protocolo = ?
");
$stmt->execute([$id_protocolo]);
$protocolo = $stmt->fetch();
if (!$protocolo) {
    echo "Protocolo no encontrado.";
    exit;
}

// Obtener muestras
$stmt = $conexion->prepare("SELECT * FROM muestras WHERE id_protocolo = ?");
$stmt->execute([$id_protocolo]);
$muestras = $stmt->fetchAll();

// Para cada muestra, obtener sus análisis
$muestras_con_analisis = [];
$total_protocolo = 0;

foreach ($muestras as $m) {
    $stmt = $conexion->prepare("
        SELECT a.nombre_estudio, ma.precio_unitario
        FROM muestra_analisis ma
        JOIN analisis_laboratorio a ON ma.id_analisis = a.id_analisis
        WHERE ma.id_muestra = ?
    ");
    $stmt->execute([$m['id_muestra']]);
    $analisis = $stmt->fetchAll();

    //$subtotal = array_sum(array_column($analisis, 'precio_unitario'));
    //$total_protocolo += $subtotal;
    $precio_unitario_total = array_sum(array_column($analisis, 'precio_unitario'));
    $subtotal = $precio_unitario_total * ($m['cantidad'] ?? 1);
    $total_protocolo += $subtotal;

    $muestras_con_analisis[] = [
        'muestra' => $m,
        'analisis' => $analisis,
        'subtotal' => $subtotal
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Boleta de Cobro</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        h2 { margin-top: 0; }
    </style>
</head>
<body>
    <div style="margin-bottom: 15px;">
    <button onclick="window.print()">🖨️ Imprimir</button>
    <button onclick="descargarPDF()">⬇️ Descargar PDF</button>
</div>
<table class="boleta-header">
        <tr>
            <td><img src="assets/larrsa.png" width="150    px">
<img src="assets/logo-fmvz.webp" width="100px"></td>
            <td class="titulo">BOLETA DE COBRO</td>
            <td>
                <strong>Código:</strong> LAR-RE-030<br>
                <strong>Versión:</strong> 6<br>
                <strong>Página:</strong> 1 de 1
            </td>
        </tr>
    </table> 

<p><strong>Protocolo No.:</strong> <?= htmlspecialchars($protocolo['protocolo_no'] ?? '') ?></p>
<p><strong>Id:</strong> <?= htmlspecialchars($protocolo['id_protocolo'] ?? '') ?></p>
<p><strong>Fecha:</strong> <?= htmlspecialchars($protocolo['fecha']) ?></p>
<p><strong>Cliente:</strong> <?= htmlspecialchars($protocolo['nombre_cliente']) ?></p>
<p><strong>Unidad Productiva:</strong> <?= htmlspecialchars($protocolo['nombre_finca']) ?></p>
<p><strong>Tipo de Protocolo:</strong> <?= htmlspecialchars($protocolo['nombre_tipo']) ?></p>

<hr>

<?php foreach ($muestras_con_analisis as $item): ?>
    <h3>Muestra ID: <?= $item['muestra']['id_muestra'] ?></h3>
    <table>
        <tr>
            <th>Cantidad</th>   
            <th>Análisis</th>
            <th>Precio (Q)</th>
            <th>Total analisis (Q)</th>
        </tr>
        <?php foreach ($item['analisis'] as $a): ?>
            <tr>
                <td> <?= $item['muestra']['cantidad'] ?></td>
                <td><?= htmlspecialchars($a['nombre_estudio']) ?></td>
                <td>Q <?= number_format($a['precio_unitario'], 2) ?></td>
                <td>Q <?= number_format(number_format($a['precio_unitario'], 2)*$item['muestra']['cantidad'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3"><strong>Subtotal Muestra</strong></td>
            <td align="left"> <strong>Q <?= number_format($item['subtotal'], 2) ?></strong></td>
        </tr>
    </table>
<?php endforeach; ?>

<h3>Total General: Q <?= number_format($total_protocolo, 2) ?></h3>

    <table class="boleta-header">
        <tr>
            <td><strong>f.</strong> ________________________</td>
            <td><strong>Vo.Bo.:</strong> ___________________</td>
        </tr>
    </table>
     <div class="footer-info">
        <p>Para que la muestra sea procesada se deberá presentar el recibo 101-C</p>
        <p><strong>CIUDAD UNIVERSITARIA, ZONA 12</strong><br>
           EDIFICIO M-10<br>
           CIUDAD DE GUATEMALA</p>
        <p><strong>TEL:</strong> (502)24188312-14 / (502)24189541<br>
           <strong>Email:</strong> LARRSA@usac.edu.gt</p>
        <p><strong>Elaboró:</strong> KC &nbsp;&nbsp;&nbsp; <strong>Revisó:</strong> FE &nbsp;&nbsp;&nbsp; <strong>Aprobó:</strong> MM &nbsp;&nbsp;&nbsp; <strong>Emisión:</strong> 25/09/2019</p>
    </div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function descargarPDF() {
    import('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js').then(() => {
        const { jsPDF } = window.jspdf;
        html2canvas(document.body).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('boleta_cobro_protocolo.pdf');
        });
    });
}
</script>
 <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 40px;
        }
        .boleta {
            width: 100%;
            border-collapse: collapse;
        }
        .boleta th, .boleta td {
            border: 1px solid black;
            padding: 5px;
        }
        .boleta-header, .boleta-footer {
            width: 100%;
            margin-bottom: 10px;
        }
        .titulo {
            font-weight: bold;
            text-align: center;
            font-size: 14px;
        }
        .copianota {
            text-align: center;
            font-weight: bold;
            margin-top: 15px;
        }
        .footer-info {
            margin-top: 30px;
            font-size: 11px;
        }
    </style>

</body>
</html>
