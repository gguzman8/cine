<?php
require_once __DIR__ . '/config/database.php';

$admin_hash  = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
$staff_hash  = password_hash('staff123', PASSWORD_BCRYPT, ['cost' => 12]);
$user_hash   = password_hash('cliente123', PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare('INSERT IGNORE INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)');
$stmt->execute(['Admin', 'admin@cine.com', $admin_hash, 'admin']);
$stmt->execute(['Staff Vendedor', 'staff@cine.com', $staff_hash, 'vendedor']);
$stmt->execute(['Carlos Pérez', 'carlos@email.com', $user_hash, 'cliente']);

echo "Usuarios creados.\n";
echo "  Admin:    admin@cine.com / admin123\n";
echo "  Staff:    staff@cine.com / staff123\n";
echo "  Cliente:  carlos@email.com / cliente123\n";
