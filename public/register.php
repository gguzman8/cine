<?php
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Cine</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="form-container">
        <h2>Registrarse</h2>
        <?php if (!empty($_SESSION['error'])): ?>
            <p class="error"><?= h($_SESSION['error']); unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <form action="../src/auth/register_handler.php" method="POST">
            <label>Nombre
                <input type="text" name="nombre" required>
            </label>
            <label>Correo electrónico
                <input type="email" name="email" required>
            </label>
            <label>Contraseña (mín. 6 caracteres)
                <input type="password" name="password" minlength="6" required>
            </label>
            <button type="submit">Crear cuenta</button>
        </form>
        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
    </main>
</body>
</html>
