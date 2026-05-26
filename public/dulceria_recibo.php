<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_login();

$compra_id = (int) ($_GET['compra_id'] ?? 0);

$es_staff = es_admin() || es_staff();

$stmt = $pdo->prepare(
    'SELECT dc.id, dc.total, dc.created_at, dc.cliente_nombre, dc.vendedor_nombre,
            dc.para_llevar, dc.boleto_id, dc.entregado,
            u.nombre AS usuario_nombre
     FROM dulceria_compras dc
     JOIN usuarios u ON u.id = dc.usuario_id
     WHERE dc.id = ?' . ($es_staff ? '' : ' AND dc.usuario_id = ?')
);
if ($es_staff) {
    $stmt->execute([$compra_id]);
} else {
    $stmt->execute([$compra_id, $_SESSION['usuario_id']]);
}
$compra = $stmt->fetch();

if (!$compra) {
    redirect('dulceria.php');
}

$detalle = $pdo->prepare(
    'SELECT p.nombre, dd.cantidad, dd.precio
     FROM dulceria_detalle dd
     JOIN productos p ON p.id = dd.producto_id
     WHERE dd.compra_id = ?'
);
$detalle->execute([$compra_id]);
$items = $detalle->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo - Cine Sendera</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera — Recibo</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?></span>
            <?php if (!$es_staff): ?>
                <a href="index.php" class="btn-outline">Cartelera</a>
            <?php endif; ?>
            <a href="dulceria.php" class="btn-outline">Dulcería</a>
            <a href="logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <div class="ticket" id="ticket" style="max-width:500px;">
            <h2>🍿 Recibo de Dulcería</h2>
            <p><strong>Compra #:</strong> <?= $compra['id'] ?></p>
            <p><strong>Cliente:</strong> <?= h($compra['cliente_nombre']) ?></p>
            <p><strong>Vendido por:</strong> <?= h($compra['vendedor_nombre']) ?></p>
            <?php if ($compra['para_llevar']): ?>
                <p><strong>🚚 Entrega en sala:</strong> Boleto #<?= $compra['boleto_id'] ?>
                    <span class="badge <?= $compra['entregado'] ? 'badge-green' : 'badge-yellow' ?>">
                        <?= $compra['entregado'] ? '✅ Entregado' : '⏳ Pendiente' ?>
                    </span>
                </p>
            <?php endif; ?>
            <table style="width:100%;margin:1rem 0;">
                <thead>
                    <tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= h($it['nombre']) ?></td>
                            <td><?= $it['cantidad'] ?></td>
                            <td>$<?= number_format($it['precio'], 2) ?></td>
                            <td>$<?= number_format($it['cantidad'] * $it['precio'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align:right;font-size:1.2rem;"><strong>Total: $<?= number_format($compra['total'], 2) ?></strong></p>
            <p><em><?= date('d/m/Y H:i', strtotime($compra['created_at'])) ?></em></p>
            <div class="ticket-actions">
                <a href="dulceria.php" class="btn">Volver a dulcería</a>
                <button onclick="window.print()" class="btn btn-print">Imprimir</button>
            </div>
        </div>
    </main>
</body>
</html>
