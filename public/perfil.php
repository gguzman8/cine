<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_login();

$user_id = $_SESSION['usuario_id'];

$boletos = $pdo->prepare(
    'SELECT c.id, c.total, c.created_at, c.checkin_at, p.titulo, f.horario, f.sala
     FROM compras c
     JOIN funciones f ON f.id = c.funcion_id
     JOIN peliculas p ON p.id = f.pelicula_id
     WHERE c.usuario_id = ?
     ORDER BY c.created_at DESC'
);
$boletos->execute([$user_id]);
$boletos = $boletos->fetchAll();

$dulceria = $pdo->prepare(
    'SELECT dc.id, dc.total, dc.created_at, dc.para_llevar, dc.entregado
     FROM dulceria_compras dc
     WHERE dc.usuario_id = ?
     ORDER BY dc.created_at DESC'
);
$dulceria->execute([$user_id]);
$dulceria = $dulceria->fetchAll();

$checkin_pendientes = $pdo->query('SELECT COUNT(*) FROM compras WHERE checkin_at IS NULL')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Cine Sendera</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera — Mi Perfil</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <a href="index.php" class="btn-outline">Cartelera</a>
            <a href="dulceria.php" class="btn-outline">Dulcería</a>
            <?php if (es_admin()): ?>
                <a href="admin.php" class="btn-outline">Panel Admin</a>
            <?php endif; ?>
            <?php if (es_staff()): ?>
                <a href="staff/vender.php" class="btn-outline">Vender boletos</a>
                <a href="staff/checkin.php" class="btn-outline">Check-in <?= $checkin_pendientes > 0 ? '<span class="badge badge-red btn-badge">' . $checkin_pendientes . '</span>' : '' ?></a>
            <?php endif; ?>
            <a href="logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>Mis Compras</h2>

        <section>
            <h3 class="section-title">🎬 Boletos de Cine <span class="count"><?= count($boletos) ?></span></h3>
            <?php if (empty($boletos)): ?>
                <p class="empty">No has comprado boletos aún. <a href="index.php">Ver cartelera</a></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Orden #</th><th>Película</th><th>Sala</th><th>Horario</th><th>Total</th><th>Check-in</th><th>Compra</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($boletos as $b): ?>
                            <tr>
                                <td><strong>#<?= $b['id'] ?></strong></td>
                                <td><?= h($b['titulo']) ?></td>
                                <td><?= h($b['sala']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($b['horario'])) ?></td>
                                <td><strong>$<?= number_format($b['total'], 2) ?></strong></td>
                                <td><span class="badge <?= $b['checkin_at'] ? 'badge-green' : 'badge-yellow' ?>"><?= $b['checkin_at'] ? 'Check-in: ' . date('H:i', strtotime($b['checkin_at'])) : 'Pendiente' ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
                                <td><a href="ticket.php?compra_id=<?= $b['id'] ?>" class="btn btn-sm btn-outline">Ver ticket</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section>
            <h3 class="section-title">🍿 Dulcería <span class="count"><?= count($dulceria) ?></span></h3>
            <?php if (empty($dulceria)): ?>
                <p class="empty">No has comprado en dulcería aún. <a href="dulceria.php">Ver productos</a></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Orden #</th><th>Total</th><th>Para llevar</th><th>Entrega</th><th>Fecha</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dulceria as $d): ?>
                            <tr>
                                <td><strong>#<?= $d['id'] ?></strong></td>
                                <td><strong>$<?= number_format($d['total'], 2) ?></strong></td>
                                <td><?= $d['para_llevar'] ? '✅ Sí' : '—' ?></td>
                                <td><span class="badge <?= $d['entregado'] ? 'badge-green' : 'badge-yellow' ?>"><?= $d['entregado'] ? 'Entregado' : 'Pendiente' ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
