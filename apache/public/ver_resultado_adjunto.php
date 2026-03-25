<?php
session_start();

$archivo = $_GET['file'] ?? '';
$archivo = basename($archivo);

$ruta = __DIR__ . "/uploads/resultados/" . $archivo;

if (!$archivo || !file_exists($ruta)) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}

$mime = mime_content_type($ruta);
header("Content-Type: " . $mime);
header("Content-Disposition: inline; filename=\"" . $archivo . "\"");
readfile($ruta);
exit;