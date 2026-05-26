<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('admin');

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, titulo, activa FROM peliculas WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    $_SESSION['error_pelicula'] = 'Película no encontrada.';
    redirect('/admin.php');
}

$nuevo_estado = $p['activa'] ? 0 : 1;
$estado_texto = $nuevo_estado ? 'activada' : 'desactivada';

$stmt = $pdo->prepare('UPDATE peliculas SET activa = ? WHERE id = ?');
$stmt->execute([$nuevo_estado, $id]);

$_SESSION['success_pelicula'] = "Película \"{$p['titulo']}\" $estado_texto.";
redirect('/admin.php');
