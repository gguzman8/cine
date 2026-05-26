<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../public/compra.php');
}

$funcion_id = (int) ($_POST['funcion_id'] ?? 0);
$cantidad   = (int) ($_POST['cantidad'] ?? 0);
$pelicula_id = (int) ($_POST['pelicula_id'] ?? 0);

if ($funcion_id <= 0 || $cantidad <= 0 || $cantidad > 10) {
    $_SESSION['error'] = 'Datos de compra inválidos.';
    redirect("../public/compra.php?pelicula_id=$pelicula_id");
}

try {
    $pdo->beginTransaction();

    $funcion = $pdo->prepare(
        'SELECT f.*, p.precio, p.titulo
         FROM funciones f
         JOIN peliculas p ON p.id = f.pelicula_id
         WHERE f.id = ? FOR UPDATE'
    );
    $funcion->execute([$funcion_id]);
    $funcion = $funcion->fetch();

    if (!$funcion) {
        throw new Exception('Función no encontrada.');
    }

    $asientos = $pdo->prepare(
        'SELECT id FROM asientos
         WHERE funcion_id = ? AND disponible = TRUE
         ORDER BY fila, numero
         LIMIT ? FOR UPDATE'
    );
    $asientos->execute([$funcion_id, $cantidad]);
    $asientos = $asientos->fetchAll();

    if (count($asientos) < $cantidad) {
        throw new Exception('No hay suficientes asientos disponibles.');
    }

    $total = $funcion['precio'] * $cantidad;

    $stmt = $pdo->prepare(
        'INSERT INTO compras (usuario_id, funcion_id, total) VALUES (?, ?, ?)'
    );
    $stmt->execute([$_SESSION['usuario_id'], $funcion_id, $total]);
    $compra_id = (int) $pdo->lastInsertId();

    $ids = array_column($asientos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $pdo->prepare(
        "UPDATE asientos SET disponible = FALSE WHERE id IN ($placeholders)"
    )->execute($ids);

    $stmt = $pdo->prepare(
        'INSERT INTO detalle_compra (compra_id, asiento_id, precio) VALUES (?, ?, ?)'
    );
    foreach ($ids as $aid) {
        $stmt->execute([$compra_id, $aid, $funcion['precio']]);
    }

    $pdo->commit();

    $_SESSION['compra_exitosa'] = $compra_id;
    redirect("../public/ticket.php?compra_id=$compra_id");

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    redirect("../public/compra.php?pelicula_id=$pelicula_id");
}
