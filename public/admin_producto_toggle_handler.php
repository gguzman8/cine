<?php
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('admin.php#dulceria');
}

try {
    require_once __DIR__ . '/../src/config/database.php';

    $stmt = $pdo->prepare('UPDATE productos SET activa = NOT activa WHERE id = ?');
    $stmt->execute([$id]);

    $_SESSION['success_producto'] = 'Estado del producto actualizado.';
    redirect('admin.php#dulceria');

} catch (\RuntimeException $e) {
    $_SESSION['error_producto'] = 'Error de base de datos.';
    redirect('admin.php#dulceria');
}
