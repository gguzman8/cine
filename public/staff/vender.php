<?php
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/includes/session.php';
require_once __DIR__ . '/../../src/includes/functions.php';
requerir_rol('admin', 'vendedor');

limpiar_funciones_expiradas($pdo);

$peliculas = $pdo->query(
    'SELECT p.*, COUNT(f.id) AS funciones_count
     FROM peliculas p
     LEFT JOIN funciones f ON f.pelicula_id = p.id AND f.expirada = FALSE
     GROUP BY p.id'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vender boletos - Cine Sendera</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera — Staff</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?> (<?= h($_SESSION['usuario_rol']) ?>)</span>
            <a href="checkin.php">Check-in</a>
            <a href="../index.php">Cartelera</a>
            <?php if (es_admin()): ?><a href="../admin.php">Panel admin</a><?php endif; ?>
            <a href="../logout.php">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>Vender boletos</h2>
        <div class="cartelera">
            <?php foreach ($peliculas as $p): ?>
                <div class="pelicula">
                    <img src="../assets/img/<?= h($p['poster']) ?>" alt="<?= h($p['titulo']) ?>">
                    <h3><?= h($p['titulo']) ?></h3>
                    <p><?= h($p['sinopsis']) ?></p>
                    <p><strong>$<?= number_format($p['precio'], 2) ?></strong></p>
                    <a href="../compra.php?pelicula_id=<?= $p['id'] ?>" class="btn">Vender</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
