<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Datos de la página 2
    $buen_estado = isset($_POST["buen_estado"]) ? 'Si' : 'No';
    $autolisis = isset($_POST["autolisis"]) ? 'Si' : 'No';
    $entrega_personal = isset($_POST["entrega_personal"]) ? 'Si' : 'No';
    $entrega_correo = isset($_POST["entrega_correo"]) ? 'Si' : 'No';
    $correo_resultado = $_POST["correo_resultado"] ?? null;
    $lesiones_necropsia = $_POST["lesiones_necropsia"];
    $bacteriologia = isset($_POST["bacteriologia"]) ? 'Si' : 'No';
    $virologia = isset($_POST["virologia"]) ? 'Si' : 'No';
    $serologia = isset($_POST["serologia"]) ? 'Si' : 'No';
    $parasitologico = isset($_POST["parasitologico"]) ? 'Si' : 'No';
    $histologico = isset($_POST["histologico"]) ? 'Si' : 'No';
    $micologico = isset($_POST["micologico"]) ? 'Si' : 'No';
    $diagnostico_necropsia = $_POST["diagnostico_necropsia"];
    $responsable = $_POST["responsable"];

    try {
        $stmt = $conexion->prepare("INSERT INTO necropsia (buen_estado, autolisis, entrega_personal, entrega_correo, correo_resultado, lesiones_necropsia, bacteriologia, virologia, serologia, parasitologico, histologico, micologico, diagnostico_necropsia, responsable) 
                                    VALUES (:buen_estado, :autolisis, :entrega_personal, :entrega_correo, :correo_resultado, :lesiones_necropsia, :bacteriologia, :virologia, :serologia, :parasitologico, :histologico, :micologico, :diagnostico_necropsia, :responsable)");
        $stmt->execute([
            ':buen_estado' => $buen_estado,
            ':autolisis' => $autolisis,
            ':entrega_personal' => $entrega_personal,
            ':entrega_correo' => $entrega_correo,
            ':correo_resultado' => $correo_resultado,
            ':lesiones_necropsia' => $lesiones_necropsia,
            ':bacteriologia' => $bacteriologia,
            ':virologia' => $virologia,
            ':serologia' => $serologia,
            ':parasitologico' => $parasitologico,
            ':histologico' => $histologico,
            ':micologico' => $micologico,
            ':diagnostico_necropsia' => $diagnostico_necropsia,
            ':responsable' => $responsable
        ]);
        header("Location: ../lista_muestras.php?success=1");
        exit();
    } catch (PDOException $e) {
        header("Location: ../ingreso_muestra.php?error=1");
        exit();
    }
}
?>
