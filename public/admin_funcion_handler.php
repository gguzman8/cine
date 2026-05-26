<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin.php');
}

$pelicula_id = (int) ($_POST['pelicula_id'] ?? 0);
$horario     = $_POST['horario'] ?? '';
$sala        = trim($_POST['sala'] ?? '');
$es_matinee  = !empty($_POST['es_matinee']) ? 1 : 0;

if ($pelicula_id <= 0 || $horario === '' || $sala === '') {
    $_SESSION['error_funcion'] = 'Todos los campos son obligatorios.';
    redirect('/admin.php');
}

$stmt = $pdo->prepare('SELECT id FROM peliculas WHERE id = ?');
$stmt->execute([$pelicula_id]);
if (!$stmt->fetch()) {
    $_SESSION['error_funcion'] = 'Película no encontrada.';
    redirect('/admin.php');
}

$stmt = $pdo->prepare(
    'INSERT INTO funciones (pelicula_id, horario, sala, es_matinee) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$pelicula_id, $horario, $sala, $es_matinee]);
$funcion_id = (int) $pdo->lastInsertId();

$pdo->exec("CALL cine.generar_asientos($funcion_id)");

$_SESSION['success_funcion'] = 'Función agregada con 40 asientos.';
redirect('/admin.php');
