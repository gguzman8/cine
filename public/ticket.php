<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_login();

$compra_id = (int) ($_GET['compra_id'] ?? 0);

$es_staff = es_admin() || es_staff();

$stmt = $pdo->prepare(
    'SELECT c.id, c.total, c.created_at, c.checkin_at, p.titulo, p.precio AS precio_base,
            f.horario, f.sala, f.es_matinee, u.nombre, u.id AS usuario_id, c.cupon_id,
            c.cliente_nombre, c.vendedor_nombre
     FROM compras c
     JOIN funciones f ON f.id = c.funcion_id
     JOIN peliculas p ON p.id = f.pelicula_id
     JOIN usuarios u ON u.id = c.usuario_id
     WHERE c.id = ?' . ($es_staff ? '' : ' AND c.usuario_id = ?')
);
if ($es_staff) {
    $stmt->execute([$compra_id]);
} else {
    $stmt->execute([$compra_id, $_SESSION['usuario_id']]);
}
$compra = $stmt->fetch();

if (!$compra) {
    redirect('index.php');
}

$detalle = $pdo->prepare(
    'SELECT a.fila, a.numero, d.precio
     FROM detalle_compra d
     JOIN asientos a ON a.id = d.asiento_id
     WHERE d.compra_id = ?'
);
$detalle->execute([$compra_id]);
$asientos = $detalle->fetchAll();

$precio_unitario = $asientos[0]['precio'] ?? 0;
$subtotal = $precio_unitario * count($asientos);
$cupon = null;
if ($compra['cupon_id']) {
    $stmt = $pdo->prepare('SELECT * FROM cupones WHERE id = ?');
    $stmt->execute([$compra['cupon_id']]);
    $cupon = $stmt->fetch();
}

$checkin_pendientes = $pdo->query('SELECT COUNT(*) FROM compras WHERE checkin_at IS NULL')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de compra - Cine Uwuntu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Uwuntu</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <?php if (!$es_staff): ?>
                <a href="index.php" class="btn-outline">Cartelera</a>
            <?php endif; ?>
            <?php if ($es_staff): ?>
                <a href="staff/vender.php" class="btn-outline">Vender</a>
                <a href="staff/checkin.php" class="btn-outline">Check-in <span class="badge badge-yellow"><?= $checkin_pendientes ?></span></a>
            <?php endif; ?>
            <a href="perfil.php" class="btn-outline">Mi Perfil</a>
            <a href="logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <div class="ticket" id="ticket">
            <h2>Ticket Digital</h2>
            <p><strong>Compra #:</strong> <?= $compra['id'] ?></p>
            <p><strong>Cliente:</strong> <?= h($compra['cliente_nombre']) ?></p>
            <p><strong>Vendido por:</strong> <?= h($compra['vendedor_nombre']) ?></p>
            <p><strong>Película:</strong> <?= h($compra['titulo']) ?></p>
            <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($compra['horario'])) ?></p>
            <p><strong>Sala:</strong> <?= h($compra['sala']) ?></p>
            <?php if ($compra['es_matinee']): ?>
                <p><strong>Función:</strong> Matiné (<?= MATINEE_DESCUENTO ?>% descuento)</p>
            <?php endif; ?>
            <p><strong>Asientos:</strong>
                <?php foreach ($asientos as $a): ?>
                    <?= h($a['fila']) . $a['numero'] ?> &nbsp;
                <?php endforeach; ?>
            </p>
            <p><strong>Precio unitario:</strong> $<?= number_format($precio_unitario, 2) ?></p>
            <?php if ($cupon): ?>
                <p><strong>Cupón:</strong> <?= h($cupon['codigo']) ?> (<?= $cupon['descuento_porcentaje'] ?>%)</p>
            <?php endif; ?>
            <p><strong>Total:</strong> $<?= number_format($compra['total'], 2) ?></p>
            <p><strong>Estado:</strong> <span class="badge <?= $compra['checkin_at'] ? 'badge-green' : 'badge-yellow' ?>"><?= $compra['checkin_at'] ? '✅ Check-in realizado' : '⏳ Pendiente' ?></span></p>
            <?php if ($compra['checkin_at']): ?>
                <p><em>Check-in: <?= date('d/m/Y H:i', strtotime($compra['checkin_at'])) ?></em></p>
            <?php endif; ?>
            <p><em><?= date('d/m/Y H:i', strtotime($compra['created_at'])) ?></em></p>
            <div class="ticket-actions">
                <a href="index.php" class="btn">Volver a la cartelera</a>
                <?php if ($es_staff && !$compra['checkin_at']): ?>
                    <form method="POST" action="staff/checkin_handler.php" style="display:inline;">
                        <input type="hidden" name="compra_id" value="<?= $compra['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn" style="background:#2e7d32;">✅ Check-in</button>
                    </form>
                <?php endif; ?>
                <button onclick="window.print()" class="btn btn-print">Imprimir ticket</button>
            </div>
        </div>
    </main>
</body>
</html>
