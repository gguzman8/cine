<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';

limpiar_funciones_expiradas($pdo);

$peliculas = $pdo->query(
    'SELECT p.*, COUNT(f.id) AS funciones_count
     FROM peliculas p
     LEFT JOIN funciones f ON f.pelicula_id = p.id AND f.expirada = FALSE
     WHERE p.activa = TRUE
     GROUP BY p.id'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cine Sendera - Cartelera</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera</h1>
        <nav>
            <?php if (esta_logueado()): ?>
                <span><?= h($_SESSION['usuario_nombre']) ?></span>
                <?php if (es_admin()): ?>
                    <a href="admin.php" class="btn-outline">Panel Admin</a>
                <?php endif; ?>
                <?php if (es_staff()): ?>
                    <a href="staff/vender.php" class="btn-outline">Vender boletos</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-muted">Cerrar sesión</a>
            <?php else: ?>
                <a href="login.php" class="btn-outline">Iniciar sesión</a>
                <a href="register.php" class="btn">Registrarse</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <h2>Cartelera</h2>
        <?php if (es_matinee_horario(date('Y-m-d H:i:s'))): ?>
            <p class="matinee-banner">Funciones matiné con <?= MATINEE_DESCUENTO ?>% de descuento (antes de 12:00, entre semana)</p>
        <?php endif; ?>
        <div class="cartelera">
            <?php foreach ($peliculas as $p): ?>
                <div class="pelicula">
                    <img src="assets/img/<?= h($p['poster']) ?>" alt="<?= h($p['titulo']) ?>">
                    <h3><?= h($p['titulo']) ?></h3>
                    <p><?= h($p['sinopsis']) ?></p>
                    <p>
                        <strong>$<?= number_format($p['precio'], 2) ?></strong>
                        <?php if (es_matinee_horario(date('Y-m-d H:i:s'))): ?>
                            <span class="matinee-price">Matiné: $<?= number_format(precio_con_matinee((float)$p['precio'], true), 2) ?></span>
                        <?php endif; ?>
                    </p>
                    <a href="compra.php?pelicula_id=<?= $p['id'] ?>" class="btn">Comprar boletos</a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
