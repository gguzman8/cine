<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../register.php');
}

$nombre   = trim($_POST['nombre'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($nombre === '' || $email === '' || $password === '') {
    $_SESSION['error'] = 'Todos los campos son obligatorios.';
    redirect('../register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Correo electrónico inválido.';
    redirect('../register.php');
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres.';
    redirect('../register.php');
}

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['error'] = 'El correo ya está registrado.';
    redirect('../register.php');
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare(
    'INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)'
);
$stmt->execute([$nombre, $email, $hash]);

$_SESSION['usuario_id']  = (int) $pdo->lastInsertId();
$_SESSION['usuario_nombre'] = $nombre;
$_SESSION['usuario_rol']    = 'cliente';
session_regenerate_id(true);

redirect('../index.php');
