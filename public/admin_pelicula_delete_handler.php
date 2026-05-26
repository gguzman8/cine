<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, titulo FROM peliculas WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    $_SESSION['error_pelicula'] = 'Película no encontrada.';
    redirect('/admin.php');
}

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM funciones WHERE pelicula_id = ?'
);
$stmt->execute([$id]);
$funciones_count = $stmt->fetchColumn();

if ($funciones_count > 0) {
    $_SESSION['error_pelicula'] = "No se puede eliminar \"{$p['titulo']}\" porque tiene $funciones_count función(es) asociadas. Desactívala en su lugar.";
    redirect('/admin.php');
}

$stmt = $pdo->prepare('DELETE FROM peliculas WHERE id = ?');
$stmt->execute([$id]);

$_SESSION['success_pelicula'] = "Película \"{$p['titulo']}\" eliminada.";
redirect('/admin.php');
