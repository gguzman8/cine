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

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM dulceria_detalle WHERE producto_id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_producto'] = 'No se puede eliminar: el producto tiene ventas registradas.';
        redirect('admin.php#dulceria');
    }

    $stmt = $pdo->prepare('DELETE FROM productos WHERE id = ?');
    $stmt->execute([$id]);

    $_SESSION['success_producto'] = 'Producto eliminado.';
    redirect('admin.php#dulceria');

} catch (\RuntimeException $e) {
    $_SESSION['error_producto'] = 'Error de base de datos.';
    redirect('admin.php#dulceria');
}
