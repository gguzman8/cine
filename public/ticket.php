<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_login();

$compra_id = (int) ($_GET['compra_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT c.id, c.total, c.created_at, p.titulo, f.horario, f.sala, u.nombre
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de compra - Cine</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main>
        <div class="ticket">
            <h2>🎫 Ticket Digital</h2>
            <p><strong>Compra #:</strong> <?= $compra['id'] ?></p>
            <p><strong>Cliente:</strong> <?= h($compra['nombre']) ?></p>
            <p><strong>Película:</strong> <?= h($compra['titulo']) ?></p>
            <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($compra['horario'])) ?></p>
            <p><strong>Sala:</strong> <?= h($compra['sala']) ?></p>
            <p><strong>Asientos:</strong>
                <?php foreach ($asientos as $a): ?>
                    <?= h($a['fila']) . $a['numero'] ?> &nbsp;
                <?php endforeach; ?>
            </p>
            <p><strong>Total:</strong> $<?= number_format($compra['total'], 2) ?></p>
            <p><em><?= date('d/m/Y H:i', strtotime($compra['created_at'])) ?></em></p>
            <a href="index.php" class="btn">Volver a la cartelera</a>
        </div>
    </main>
</body>
</html>
