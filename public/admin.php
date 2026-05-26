<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_login();

if (!es_admin()) {
    redirect('index.php');
}

$total_usuarios  = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$total_compras   = $pdo->query('SELECT COUNT(*) FROM compras')->fetchColumn();
$total_ingresos  = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM compras')->fetchColumn();
$total_peliculas = $pdo->query('SELECT COUNT(*) FROM peliculas')->fetchColumn();

$ultimas_compras = $pdo->query(
    'SELECT c.id, u.nombre AS usuario, p.titulo, c.total, c.created_at
     FROM compras c
     JOIN usuarios u ON u.id = c.usuario_id
     JOIN funciones f ON f.id = c.funcion_id
     JOIN peliculas p ON p.id = f.pelicula_id
     ORDER BY c.created_at DESC
     LIMIT 10'
)->fetchAll();

$funciones_hoy = $pdo->query(
    'SELECT f.*, p.titulo,
        (SELECT COUNT(*) FROM asientos a WHERE a.funcion_id = f.id AND a.disponible = TRUE) AS libres
     FROM funciones f
     JOIN peliculas p ON p.id = f.pelicula_id
     WHERE DATE(f.horario) = CURDATE()
     ORDER BY f.horario'
)->fetchAll();

$cupones = $pdo->query('SELECT * FROM cupones ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Cine Sendera</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera — Admin</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <a href="index.php">Cartelera</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>Panel de Administración</h2>

        <section class="admin-stats">
            <div class="stat-card">
                <h3><?= $total_peliculas ?></h3>
                <p>Películas</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_usuarios ?></h3>
                <p>Usuarios</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_compras ?></h3>
                <p>Compras</p>
            </div>
            <div class="stat-card">
                <h3>$<?= number_format($total_ingresos, 2) ?></h3>
                <p>Ingresos totales</p>
            </div>
        </section>

        <section>
            <h3>Funciones de Hoy</h3>
            <table>
                <thead>
                    <tr><th>Película</th><th>Horario</th><th>Sala</th><th>Asientos libres</th><th>Matiné</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($funciones_hoy as $f): ?>
                        <tr>
                            <td><?= h($f['titulo']) ?></td>
                            <td><?= date('H:i', strtotime($f['horario'])) ?></td>
                            <td><?= h($f['sala']) ?></td>
                            <td><?= $f['libres'] ?>/40</td>
                            <td><?= $f['es_matinee'] ? 'Sí' : 'No' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h3>Últimas Compras</h3>
            <table>
                <thead>
                    <tr><th>#</th><th>Usuario</th><th>Película</th><th>Total</th><th>Fecha</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimas_compras as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= h($c['usuario']) ?></td>
                            <td><?= h($c['titulo']) ?></td>
                            <td>$<?= number_format($c['total'], 2) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h3>Cupones de Descuento</h3>
            <table>
                <thead>
                    <tr><th>Código</th><th>Descuento</th><th>Usos</th><th>Máximo</th><th>Activo</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($cupones as $cp): ?>
                        <tr>
                            <td><?= h($cp['codigo']) ?></td>
                            <td><?= $cp['descuento_porcentaje'] ?>%</td>
                            <td><?= $cp['usos_actuales'] ?></td>
                            <td><?= $cp['usos_maximos'] ?></td>
                            <td><?= $cp['activo'] ? 'Sí' : 'No' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
