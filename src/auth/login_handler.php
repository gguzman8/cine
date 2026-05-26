<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Ingresa correo y contraseña.';
    redirect('/login.php');
}

$stmt = $pdo->prepare(
    'SELECT id, nombre, email, password_hash, rol FROM usuarios WHERE email = ?'
);
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
    $_SESSION['error'] = 'Credenciales inválidas.';
    redirect('/login.php');
}

session_regenerate_id(true);
$_SESSION['usuario_id']     = (int) $usuario['id'];
$_SESSION['usuario_nombre'] = $usuario['nombre'];
$_SESSION['usuario_rol']    = $usuario['rol'];

redirect('/index.php');
