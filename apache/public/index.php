<?php include "views/header.php"; ?>
<?php if (isset($_GET['sesion']) && $_GET['sesion'] === 'expirada'): ?>
    <div class="alerta">
        Tu sesión ha expirado por inactividad. Por favor, inicia sesión de nuevo.
    </div>
<?php endif; ?>
<h2>Inicio de Sesión</h2>

<?php
if (isset($_GET["error"]) && $_GET["error"] == 1) {
    echo '<div class="error-msg">Credenciales incorrectas. Por favor, inténtelo de nuevo.</div>';
}
if (isset($_GET["error"]) && $_GET["error"] == 2) {
    echo '<div class="error-msg">Usuario inactivo.</div>';
}
?>

<form action="controllers/auth.php" method="POST">
    <label>Correo Electrónico:</label>
    <input type="email" name="correo" required>
    <label>Contraseña:</label>
    <input type="password" name="contrasena" required>
    <button type="submit">Iniciar Sesión</button>    
</form>
<p>¿No tienes cuenta? <a href="registro_cliente.php">Regístrate aquí</a></p>

<?php include "views/footer.php"; ?>