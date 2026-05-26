<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin.php');
}

$titulo   = trim($_POST['titulo'] ?? '');
$sinopsis = trim($_POST['sinopsis'] ?? '');
$precio   = (float) ($_POST['precio'] ?? 0);
$poster   = trim($_POST['poster'] ?? 'default.svg');

if ($titulo === '' || $precio <= 0) {
    $_SESSION['error_pelicula'] = 'Título y precio son obligatorios.';
    redirect('/admin.php');
}

$stmt = $pdo->prepare('SELECT id FROM peliculas WHERE titulo = ?');
$stmt->execute([$titulo]);
if ($stmt->fetch()) {
    $_SESSION['error_pelicula'] = 'Ya existe una película con ese título.';
    redirect('/admin.php');
}

$stmt = $pdo->prepare(
    'INSERT INTO peliculas (titulo, sinopsis, precio, poster) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$titulo, $sinopsis, $precio, $poster]);
$pelicula_id = (int) $pdo->lastInsertId();

$_SESSION['success_pelicula'] = "Película \"$titulo\" creada. Ahora puedes agregar funciones.";
$_SESSION['nueva_pelicula_id'] = $pelicula_id;
redirect('/admin.php#funciones');
