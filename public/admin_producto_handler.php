<?php
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
}

$nombre = trim($_POST['nombre'] ?? '');
$precio = (float) ($_POST['precio'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');

if ($nombre === '' || $precio <= 0) {
    $_SESSION['error_producto'] = 'Nombre y precio válidos son requeridos.';
    redirect('admin.php#dulceria');
}

try {
    require_once __DIR__ . '/../src/config/database.php';

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE nombre = ?');
    $stmt->execute([$nombre]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_producto'] = 'Ya existe un producto con ese nombre.';
        redirect('admin.php#dulceria');
    }

    $stmt = $pdo->prepare('INSERT INTO productos (nombre, descripcion, precio) VALUES (?, ?, ?)');
    $stmt->execute([$nombre, $descripcion, $precio]);

    $_SESSION['success_producto'] = 'Producto agregado correctamente.';
    redirect('admin.php#dulceria');

} catch (\RuntimeException $e) {
    $_SESSION['error_producto'] = 'Error de base de datos.';
    redirect('admin.php#dulceria');
}
