<?php
session_start();
require_once "config/database.php";

$archivo = $_GET['file'] ?? '';
$archivo = basename($archivo);

if ($archivo === '') {
    http_response_code(400);
    exit('Archivo no especificado.');
}

$rutas = [
    __DIR__ . '/uploads/resultados/' . $archivo,
    dirname(__DIR__) . '/uploads/resultados/' . $archivo,
];

$rutaEncontrada = null;
foreach ($rutas as $ruta) {
    if (file_exists($ruta)) {
        $rutaEncontrada = $ruta;
        break;
    }
}

if (!$rutaEncontrada) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$mime = mime_content_type($rutaEncontrada) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($rutaEncontrada));
header('Content-Disposition: inline; filename="' . rawurlencode($archivo) . '"');
readfile($rutaEncontrada);
exit;
