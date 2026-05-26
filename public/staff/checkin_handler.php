<?php
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/includes/session.php';
require_once __DIR__ . '/../../src/includes/functions.php';
requerir_rol('admin', 'vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('checkin.php');
}

if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['checkin_error'] = 'Token CSRF inválido.';
    redirect('checkin.php');
}

$compra_id = (int) ($_POST['compra_id'] ?? 0);

if ($compra_id <= 0) {
    $_SESSION['checkin_error'] = 'ID de compra inválido.';
    redirect('checkin.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id, checkin_at FROM compras WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$compra_id]);
    $compra = $stmt->fetch();

    if (!$compra) {
        throw new Exception('Compra no encontrada.');
    }

    if ($compra['checkin_at']) {
        throw new Exception('Esta compra ya fue registrada el ' . date('d/m/Y H:i', strtotime($compra['checkin_at'])));
    }

    $pdo->prepare('UPDATE compras SET checkin_at = NOW() WHERE id = ?')
        ->execute([$compra_id]);

    $pdo->commit();

    $_SESSION['checkin_success'] = "✅ Check-in exitoso para la compra #$compra_id.";
    redirect("checkin.php?compra_id=$compra_id");

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['checkin_error'] = $e->getMessage();
    redirect("checkin.php?compra_id=$compra_id");
}
