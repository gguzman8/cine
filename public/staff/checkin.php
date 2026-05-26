<?php
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/includes/session.php';
require_once __DIR__ . '/../../src/includes/functions.php';
requerir_rol('admin', 'vendedor');

$checkin_pendientes = $pdo->query('SELECT COUNT(*) FROM compras WHERE checkin_at IS NULL')->fetchColumn();

$compra_id = (int) ($_GET['compra_id'] ?? ($_POST['compra_id'] ?? 0));
$compra = null;
$asientos = [];
$error = '';
$success = '';

if (!empty($_SESSION['checkin_error'])) {
    $error = $_SESSION['checkin_error'];
    unset($_SESSION['checkin_error']);
}
if (!empty($_SESSION['checkin_success'])) {
    $success = $_SESSION['checkin_success'];
    unset($_SESSION['checkin_success']);
}

if ($compra_id > 0) {
    $stmt = $pdo->prepare(
        'SELECT c.id, c.total, c.created_at, c.checkin_at, c.usuario_id,
                p.titulo, p.precio AS precio_base, f.horario, f.sala, f.es_matinee,
                u.nombre AS cliente_nombre, u.email AS cliente_email
         FROM compras c
         JOIN funciones f ON f.id = c.funcion_id
         JOIN peliculas p ON p.id = f.pelicula_id
         JOIN usuarios u ON u.id = c.usuario_id
         WHERE c.id = ?'
    );
    $stmt->execute([$compra_id]);
    $compra = $stmt->fetch();

    if ($compra) {
        $detalle = $pdo->prepare(
            'SELECT a.fila, a.numero, d.precio
             FROM detalle_compra d
             JOIN asientos a ON a.id = d.asiento_id
             WHERE d.compra_id = ?'
        );
        $detalle->execute([$compra_id]);
        $asientos = $detalle->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in - Cine Sendera</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cine Sendera — Check-in</h1>
        <nav>
            <span><?= h($_SESSION['usuario_nombre']) ?> (<?= h($_SESSION['usuario_rol']) ?>)</span>
            <a href="vender.php" class="btn-outline">Vender</a>
            <a href="checkin.php" class="btn-outline">Check-in <span class="badge badge-yellow"><?= $checkin_pendientes ?></span></a>
            <?php if (es_admin()): ?><a href="../admin.php" class="btn-outline">Panel admin</a><?php endif; ?>
            <a href="../perfil.php" class="btn-outline">Mi Perfil</a>
            <a href="../logout.php" class="btn-muted">Cerrar sesión</a>
        </nav>
    </header>
    <main>
        <h2>🎟️ Check-in de Clientes</h2>
        <p style="color:#999;margin-bottom:1.5rem;">Escanea o ingresa el código QR del ticket para registrar la entrada.</p>

        <?php if ($error): ?>
            <p class="alert alert-error"><?= h($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="alert alert-success"><?= h($success) ?></p>
        <?php endif; ?>

        <form method="GET" action="checkin.php" class="form-stacked" style="max-width:500px;">
            <label>Buscar compra por ID
                <input type="number" name="compra_id" placeholder="Ingresa el # de compra" value="<?= $compra_id ?: '' ?>" min="1" required autofocus>
            </label>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>

        <?php if ($compra): ?>
            <section>
                <h3 class="section-title">📋 Datos de la compra #<?= $compra['id'] ?></h3>
                <table class="detail">
                    <tr><th>Cliente</th><td><?= h($compra['cliente_nombre']) ?> (<?= h($compra['cliente_email']) ?>)</td></tr>
                    <tr><th>Película</th><td><?= h($compra['titulo']) ?></td></tr>
                    <tr><th>Horario</th><td><?= date('d/m/Y H:i', strtotime($compra['horario'])) ?></td></tr>
                    <tr><th>Sala</th><td><?= h($compra['sala']) ?></td></tr>
                    <tr><th>Asientos</th>
                        <td>
                            <?php foreach ($asientos as $a): ?>
                                <span class="badge badge-blue"><?= h($a['fila']) . $a['numero'] ?></span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr><th>Total</th><td><strong>$<?= number_format($compra['total'], 2) ?></strong></td></tr>
                    <tr><th>Estado</th>
                        <td>
                            <?php if ($compra['checkin_at']): ?>
                                <span class="badge badge-green">✅ Check-in: <?= date('d/m/Y H:i', strtotime($compra['checkin_at'])) ?></span>
                            <?php else: ?>
                                <span class="badge badge-yellow">⏳ Pendiente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php if (!$compra['checkin_at']): ?>
                    <form method="POST" action="checkin_handler.php" style="margin-top:1rem;">
                        <input type="hidden" name="compra_id" value="<?= $compra['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('¿Confirmar check-in para <?= h($compra['cliente_nombre']) ?>?')">✅ Registrar Check-in</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['compra_id'])): ?>
            <p style="color:#e50914;margin-top:1rem;">No se encontró ninguna compra con el ID #<?= (int)$_GET['compra_id'] ?>.</p>
        <?php endif; ?>
    </main>
</body>
</html>
