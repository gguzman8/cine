<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin.php');
}

$nombre   = trim($_POST['nombre'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($nombre === '' || $email === '' || $password === '') {
    $_SESSION['error_staff'] = 'Todos los campos son obligatorios.';
    redirect('/admin.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_staff'] = 'Correo electrónico inválido.';
    redirect('/admin.php');
}

if (strlen($password) < 6) {
    $_SESSION['error_staff'] = 'La contraseña debe tener al menos 6 caracteres.';
    redirect('/admin.php');
}

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['error_staff'] = 'El correo ya está registrado.';
    redirect('/admin.php');
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare(
    'INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$nombre, $email, $hash, 'vendedor']);

$_SESSION['success_staff'] = "Staff $nombre creado exitosamente.";
redirect('/admin.php');
