<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM peliculas WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    redirect('/admin.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar película - Cine Sendera</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera — Editar película</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <a href="admin.php" class="btn-outline">Panel</a>
            <a href="perfil.php" class="btn-outline">Mi Perfil</a>
            <a href="logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>Editar: <?= h($p['titulo']) ?></h2>
        <div class="form-container">
            <form action="admin_pelicula_update_handler.php" method="POST">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <label>Título
                    <input type="text" name="titulo" value="<?= h($p['titulo']) ?>" required>
                </label>
                <label>Sinopsis
                    <textarea name="sinopsis" rows="4"><?= h($p['sinopsis']) ?></textarea>
                </label>
                <label>Precio ($)
                    <input type="number" name="precio" step="0.01" min="1" value="<?= $p['precio'] ?>" required>
                </label>
                <label>Poster (nombre del archivo)
                    <input type="text" name="poster" value="<?= h($p['poster']) ?>">
                </label>
                <button type="submit" class="btn">Guardar cambios</button>
                <a href="admin.php" class="btn" style="background:#555;">Cancelar</a>
            </form>
        </div>
    </main>
</body>
</html>
