<?php
session_start();
require_once "config/helpers.php";
?>

<?php include "views/header.php"; ?>

<div class="main-content">
    
    <h2>Registro de Nuevo Cliente</h2>
    <?php if (isset($_GET['msg'])): ?>
    <?php
    $mensajes = [
        'exito' => ['text' => '✅ Registro completado correctamente.', 'color' => '#e8f5e9', 'textColor' => '#1b5e20'],
        'error' => ['text' => '❌ Ha ocurrido un error. Intenta nuevamente.', 'color' => '#ffebee', 'textColor' => '#b71c1c'],
        'captcha' => ['text' => '⚠️ Por favor completa el captcha antes de continuar.', 'color' => '#fff3e0', 'textColor' => '#e65100'],
        'correo_duplicado' => ['text' => '⚠️ El correo ya está registrado. Intenta con otro.', 'color' => '#fff3e0', 'textColor' => '#e65100']
    ];
    $msg = $_GET['msg'];
    $alerta = $mensajes[$msg] ?? null;
    ?>
    <?php if ($alerta): ?>
        <div style="background-color: <?= $alerta['color'] ?>; color: <?= $alerta['textColor'] ?>; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <?= $alerta['text'] ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

    <form action="controllers/registro_cliente.php" method="POST">
        <label>Nombre del Cliente:</label>
        <input type="text" name="nombre_cliente" required>        

        <label>Teléfono:</label>
        <input type="text" name="telefono">

        <label>Correo Electrónico:</label>
        <input type="email" name="correo" required>

        <label>Dirección:</label>
        <input type="text" name="direccion" required>

        <label>Nombre de Usuario:</label>
        <input type="text" name="usuario" required>

        <label>Contraseña:</label>
        <input type="password" name="contrasena" required>
        <div class="g-recaptcha" data-sitekey="6Le7gCIrAAAAAJ2hziTsXdBo9lSGWSjjRHEi8wq_"></div>
        <button type="submit">Registrarse</button>
    </form>
</div>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php include "views/footer.php"; ?>
