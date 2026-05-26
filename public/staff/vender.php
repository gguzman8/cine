<?php
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/includes/session.php';
require_once __DIR__ . '/../../src/includes/functions.php';
requerir_rol('admin', 'vendedor');

limpiar_funciones_expiradas($pdo);

$checkin_pendientes = $pdo->query('SELECT COUNT(*) FROM compras WHERE checkin_at IS NULL')->fetchColumn();

$entregas_pendientes = $pdo->query(
    'SELECT dc.id AS compra_id, dc.cliente_nombre, dc.total, dc.created_at,
            dc.boleto_id, p.nombre AS producto, dd.cantidad
     FROM dulceria_compras dc
     JOIN dulceria_detalle dd ON dd.compra_id = dc.id
     JOIN productos p ON p.id = dd.producto_id
     WHERE dc.para_llevar = TRUE AND dc.entregado = FALSE
     ORDER BY dc.created_at'
)->fetchAll();

$peliculas = $pdo->query(
    'SELECT p.*
     FROM peliculas p'
)->fetchAll();

$funciones = $pdo->query(
    'SELECT f.id, f.horario, f.sala, f.es_matinee, p.id AS pelicula_id, p.titulo, p.precio AS precio_base
     FROM funciones f
     JOIN peliculas p ON p.id = f.pelicula_id
     WHERE f.expirada = FALSE
     ORDER BY f.horario'
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
            <span><?= h($_SESSION['usuario_nombre']) ?> (vendedor)</span>
            <a href="vender.php" class="btn-outline">Vender</a>
            <a href="checkin.php" class="btn-outline">Check-in <span class="badge badge-yellow"><?= $checkin_pendientes ?></span></a>
            <a href="../dulceria.php" class="btn-outline">Dulcería</a>
            <?php if (count($entregas_pendientes) > 0): ?>
                <span class="badge badge-yellow" style="font-size:.75rem;">🚚 <?= count($entregas_pendientes) ?> entrega(s)</span>
            <?php endif; ?>
            <a href="../perfil.php" class="btn-outline">Mi Perfil</a>
            <a href="../logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>Vender boletos</h2>

        <?php if (!empty($_SESSION['error_entrega'])): ?>
            <p class="alert alert-error"><?= h($_SESSION['error_entrega']); unset($_SESSION['error_entrega']); ?></p>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success_entrega'])): ?>
            <p class="alert alert-success"><?= h($_SESSION['success_entrega']); unset($_SESSION['success_entrega']); ?></p>
        <?php endif; ?>

        <?php if (count($entregas_pendientes) > 0): ?>
            <section>
                <h3 class="section-title">🚚 Entregas Pendientes en Sala <span class="count"><?= count($entregas_pendientes) ?></span></h3>
                <table>
                    <thead>
                        <tr><th>Compra</th><th>Cliente</th><th>Producto</th><th>Cant.</th><th>Total</th><th>Boleto #</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entregas_pendientes as $e): ?>
                            <tr>
                                <td><?= $e['compra_id'] ?></td>
                                <td><?= h($e['cliente_nombre']) ?></td>
                                <td><?= h($e['producto']) ?></td>
                                <td><?= $e['cantidad'] ?></td>
                                <td>$<?= number_format($e['total'], 2) ?></td>
                                <td><strong>Sala <?= $e['boleto_id'] ?></strong></td>
                                <td>
                                    <form method="POST" action="entregar_dulces_handler.php" style="display:inline;">
                                        <input type="hidden" name="compra_id" value="<?= $e['compra_id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <button type="submit" class="btn btn-sm" style="background:#2e7d32;">✅ Entregado</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <section>
            <h3 class="section-title">🎬 Películas</h3>
            <div class="cartelera">
                <?php foreach ($peliculas as $p): ?>
                    <div class="pelicula">
                        <img src="../assets/img/<?= h($p['poster']) ?>" alt="<?= h($p['titulo']) ?>">
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
                            <td><a href="../compra.php?pelicula_id=<?= $f['pelicula_id'] ?>" class="btn">Vender</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
