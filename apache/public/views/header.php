 <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARRSA</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
<script src="js/muestras.js"></script>
<header>
    <h1><img src="assets/larrsa.png" width="100px">
<img src="assets/logo-fmvz.webp" width="100px"></h1>

    <?php if (isset($_SESSION["usuario"])): ?>
        <div class="user-info">
            <p>Usuario: <strong><?php echo $_SESSION["usuario"]["nombre"]; ?></strong></p>
            <p>Rol: <strong><?php echo ucfirst($_SESSION["usuario"]["rol_nombre"]); ?></strong></p>
            <p><a href="logout.php" class="logout-link">Cerrar Sesión</a></p>
        </div>
    <?php endif; ?>
</header>

<style>
header {
    background-color: #37474f;
    color: white;
  /*  padding: 1em;*/
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.user-info {
    text-align: right;
    
}

.user-info p {
    margin: 0;
    font-size: 0.9em;
    color: white;
}

.logout-link {
    color: white;
    text-decoration: underline;
}

.logout-link:hover {
    color: #ffcccb;
}
</style>
