<?php
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php#dulceria');
}

$id          = (int) ($_POST['id'] ?? 0);
$nombre      = trim($_POST['nombre'] ?? '');
$precio      = (float) ($_POST['precio'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');

if ($id <= 0 || $nombre === '' || $precio <= 0) {
    $_SESSION['error_producto'] = 'Datos inválidos.';
    redirect('admin_producto_edit.php?id=' . $id);
}

try {
    require_once __DIR__ . '/../src/config/database.php';

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE nombre = ? AND id != ?');
    $stmt->execute([$nombre, $id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_producto'] = 'Ya existe otro producto con ese nombre.';
        redirect('admin_producto_edit.php?id=' . $id);
    }

    $stmt = $pdo->prepare('UPDATE productos SET nombre = ?, descripcion = ?, precio = ? WHERE id = ?');
    $stmt->execute([$nombre, $descripcion, $precio, $id]);

    $_SESSION['success_producto'] = 'Producto actualizado correctamente.';
    redirect('admin_producto_edit.php?id=' . $id);

} catch (\RuntimeException $e) {
    $_SESSION['error_producto'] = 'Error de base de datos.';
    redirect('admin_producto_edit.php?id=' . $id);
}
