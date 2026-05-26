<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin.php');
}

$id       = (int) ($_POST['id'] ?? 0);
$titulo   = trim($_POST['titulo'] ?? '');
$sinopsis = trim($_POST['sinopsis'] ?? '');
$precio   = (float) ($_POST['precio'] ?? 0);
$poster   = trim($_POST['poster'] ?? 'default.svg');

if ($id <= 0 || $titulo === '' || $precio <= 0) {
    $_SESSION['error_pelicula'] = 'Datos inválidos.';
    redirect('/admin.php');
}

$stmt = $pdo->prepare(
    'UPDATE peliculas SET titulo = ?, sinopsis = ?, precio = ?, poster = ? WHERE id = ?'
);
$stmt->execute([$titulo, $sinopsis, $precio, $poster, $id]);

$_SESSION['success_pelicula'] = "Película \"$titulo\" actualizada.";
redirect('/admin.php');
