<?php
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/includes/session.php';
require_once __DIR__ . '/../../src/includes/functions.php';
requerir_rol('admin', 'vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('vender.php');
}

if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_entrega'] = 'Token CSRF inválido.';
    redirect('vender.php');
}

$compra_id = (int) ($_POST['compra_id'] ?? 0);

if ($compra_id <= 0) {
    $_SESSION['error_entrega'] = 'ID de compra inválido.';
    redirect('vender.php');
}

try {
    $stmt = $pdo->prepare(
        'UPDATE dulceria_compras SET entregado = TRUE WHERE id = ? AND para_llevar = TRUE AND entregado = FALSE'
    );
    $stmt->execute([$compra_id]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['error_entrega'] = 'La compra no existe o ya fue entregada.';
    } else {
        $_SESSION['success_entrega'] = "✅ Entrega marcada para la compra #$compra_id.";
    }
} catch (\RuntimeException $e) {
    $_SESSION['error_entrega'] = 'Error de base de datos.';
}

redirect('vender.php');
