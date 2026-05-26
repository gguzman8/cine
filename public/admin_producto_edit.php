<?php
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('admin.php#dulceria');
}

try {
    require_once __DIR__ . '/../src/config/database.php';
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        $_SESSION['error_producto'] = 'Producto no encontrado.';
        redirect('admin.php#dulceria');
    }
} catch (\RuntimeException $e) {
    $_SESSION['error_producto'] = 'Error de base de datos.';
    redirect('admin.php#dulceria');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Cine Sendera</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera — Editar Producto</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <a href="admin.php#dulceria" class="btn-outline">← Volver</a>
            <a href="perfil.php" class="btn-outline">Mi Perfil</a>
            <a href="logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>Editar: <?= h($producto['nombre']) ?></h2>

        <?php if (!empty($_SESSION['error_producto'])): ?>
            <p class="alert alert-error"><?= h($_SESSION['error_producto']); unset($_SESSION['error_producto']); ?></p>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success_producto'])): ?>
            <p class="alert alert-success"><?= h($_SESSION['success_producto']); unset($_SESSION['success_producto']); ?></p>
        <?php endif; ?>

        <form action="admin_producto_update_handler.php" method="POST" class="form-stacked">
            <input type="hidden" name="id" value="<?= $producto['id'] ?>">
            <label>Nombre
                <input type="text" name="nombre" value="<?= h($producto['nombre']) ?>" required>
            </label>
            <label>Descripción
                <input type="text" name="descripcion" value="<?= h($producto['descripcion'] ?? '') ?>">
            </label>
            <label>Precio ($)
                <input type="number" name="precio" step="0.01" min="1" value="<?= $producto['precio'] ?>" required>
            </label>
            <button type="submit" class="btn">Guardar cambios</button>
        </form>
    </main>
</body>
</html>
