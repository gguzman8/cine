<?php
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Cine</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <a href="index.php" style="text-decoration:none;color:inherit;"><h1>Cine Sendera</h1></a>
    </header>
    <main class="form-container">
        <h2>Iniciar sesión</h2>
        <?php if (!empty($_SESSION['error'])): ?>
            <p class="error"><?= h($_SESSION['error']); unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <form action="login_handler.php" method="POST">
            <label>Correo electrónico
                <input type="email" name="email" required>
            </label>
            <label>Contraseña
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="btn">Entrar</button>
        </form>
        <p class="form-link">¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
    </main>
</body>
</html>
