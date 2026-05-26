<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../libs/phpqrcode/qrlib.php';
requerir_login();

$compra_id = (int) ($_GET['compra_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT c.id, c.total, c.created_at, p.titulo, p.precio AS precio_base, f.horario, f.sala, f.es_matinee, u.nombre, c.cupon_id
     FROM compras c
     JOIN funciones f ON f.id = c.funcion_id
     JOIN peliculas p ON p.id = f.pelicula_id
     JOIN usuarios u ON u.id = c.usuario_id
     WHERE c.id = ? AND c.usuario_id = ?'
);
$stmt->execute([$compra_id, $_SESSION['usuario_id']]);
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

$qr = QRCode::fromString('CINE:' . $compra['id'] . '|' . $compra['titulo'] . '|' . $compra['horario']);
$qr_svg = $qr->toSvg(180);
$qr_png = $qr->toPngBase64(180);

$precio_unitario = $asientos[0]['precio'] ?? 0;
$subtotal = $precio_unitario * count($asientos);
$cupon = null;
if ($compra['cupon_id']) {
    $stmt = $pdo->prepare('SELECT * FROM cupones WHERE id = ?');
    $stmt->execute([$compra['cupon_id']]);
    $cupon = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de compra - Cine Sendera</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <a href="index.php" class="btn-outline">Cartelera</a>
            <a href="logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <div class="ticket" id="ticket">
            <h2>Ticket Digital</h2>
            <div class="ticket-qr">
                <?= $qr_svg ?>
            </div>
            <p><strong>Compra #:</strong> <?= $compra['id'] ?></p>
            <p><strong>Cliente:</strong> <?= h($compra['nombre']) ?></p>
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
            <p><em><?= date('d/m/Y H:i', strtotime($compra['created_at'])) ?></em></p>
            <div class="ticket-actions">
                <a href="index.php" class="btn">Volver a la cartelera</a>
                <button onclick="window.print()" class="btn btn-print">Imprimir ticket</button>
            </div>
        </div>
    </main>
</body>
</html>
