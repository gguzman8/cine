<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';

limpiar_funciones_expiradas($pdo);

$peliculas = $pdo->query(
    'SELECT p.*
     FROM peliculas p
     WHERE p.activa = TRUE'
)->fetchAll();

$funciones = $pdo->query(
    'SELECT f.id, f.horario, f.sala, f.es_matinee, p.id AS pelicula_id, p.titulo, p.precio AS precio_base
     FROM funciones f
     JOIN peliculas p ON p.id = f.pelicula_id
     WHERE p.activa = TRUE AND f.expirada = FALSE
     ORDER BY f.horario'
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
                <a href="dulceria.php" class="btn-outline">Dulcería</a>
                <a href="perfil.php" class="btn-outline">Mi Perfil</a>
                <a href="logout.php" class="btn-muted">Cerrar sesión</a>
            <?php else: ?>
                <a href="dulceria.php" class="btn-outline">Dulcería</a>
                <a href="login.php" class="btn-outline">Iniciar sesión</a>
                <a href="register.php" class="btn">Registrarse</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <h2>Cartelera</h2>

        <section>
            <h3 class="section-title">🎬 Películas</h3>
            <div class="cartelera">
                <?php foreach ($peliculas as $p): ?>
                    <div class="pelicula">
                        <img src="assets/img/<?= h($p['poster']) ?>" alt="<?= h($p['titulo']) ?>">
                        <h3><?= h($p['titulo']) ?></h3>
                        <p><?= h($p['sinopsis']) ?></p>
                        <p><strong>$<?= number_format($p['precio'], 2) ?></strong></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <h3 class="section-title">📅 Funciones Disponibles</h3>
            <?php if (es_matinee_horario(date('Y-m-d H:i:s'))): ?>
                <p class="matinee-banner">Funciones matiné con <?= MATINEE_DESCUENTO ?>% de descuento (antes de 12:00)</p>
            <?php endif; ?>
            <table>
                <thead>
                    <tr><th>Película</th><th>Horario</th><th>Sala</th><th>Precio</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($funciones as $f):
                        $es_matinee = (bool)$f['es_matinee'];
                        $descuento_activo = $es_matinee && (int) date('G') < 12;
                        $precio = precio_con_matinee((float)$f['precio_base'], $es_matinee);
                    ?>
                        <tr class="<?= $descuento_activo ? 'matinee-row' : '' ?>">
                            <td><?= h($f['titulo']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($f['horario'])) ?></td>
                            <td><?= h($f['sala']) ?></td>
                            <td class="precio-cell<?= $descuento_activo ? ' matinee-price' : '' ?>">
                                <?php if ($descuento_activo): ?>
                                    <span class="precio-original">$<?= number_format((float)$f['precio_base'], 2) ?></span>
                                <?php endif; ?>
                                <span class="precio-final">$<?= number_format($precio, 2) ?></span>
                            </td>
                            <td><a href="compra.php?pelicula_id=<?= $f['pelicula_id'] ?>" class="btn">Comprar</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
