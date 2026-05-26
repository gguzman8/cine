<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../../public/compra.php');
}

if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Token CSRF inválido.';
    redirect('../../public/compra.php');
}

$funcion_id  = (int) ($_POST['funcion_id'] ?? 0);
$cantidad    = (int) ($_POST['cantidad'] ?? 0);
$pelicula_id = (int) ($_POST['pelicula_id'] ?? 0);
$cupon_codigo = trim($_POST['cupon'] ?? '');

if ($funcion_id <= 0 || $cantidad <= 0 || $cantidad > 10) {
    $_SESSION['error'] = 'Datos de compra inválidos.';
    redirect("../../public/compra.php?pelicula_id=$pelicula_id");
}

try {
    $pdo->beginTransaction();

    $funcion = $pdo->prepare(
        'SELECT f.*, p.precio AS precio_base, p.titulo
         FROM funciones f
         JOIN peliculas p ON p.id = f.pelicula_id
         WHERE f.id = ? FOR UPDATE'
    );
    $funcion->execute([$funcion_id]);
    $funcion = $funcion->fetch();

    if (!$funcion) {
        throw new Exception('Función no encontrada.');
    }

    $precio_unitario = precio_con_matinee((float)$funcion['precio_base'], (bool)$funcion['es_matinee']);

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

    $subtotal = $precio_unitario * $cantidad;

    $cupon_id = null;
    $descuento = 0;
    if ($cupon_codigo !== '') {
        $cupon = validar_cupon($pdo, $cupon_codigo);
        if (!$cupon) {
            throw new Exception('Cupón inválido o agotado.');
        }
        $cupon_id = (int)$cupon['id'];
        $descuento = round($subtotal * ((int)$cupon['descuento_porcentaje'] / 100), 2);
    }

    $total = $subtotal - $descuento;
    if ($total < 0) $total = 0;

    $stmt = $pdo->prepare(
        'INSERT INTO compras (usuario_id, funcion_id, cupon_id, total) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$_SESSION['usuario_id'], $funcion_id, $cupon_id, $total]);
    $compra_id = (int) $pdo->lastInsertId();

    if ($cupon_id) {
        aplicar_cupon($pdo, $cupon_id);
    }

    $ids = array_column($asientos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $pdo->prepare(
        "UPDATE asientos SET disponible = FALSE WHERE id IN ($placeholders)"
    )->execute($ids);

    $stmt = $pdo->prepare(
        'INSERT INTO detalle_compra (compra_id, asiento_id, precio) VALUES (?, ?, ?)'
    );
    foreach ($ids as $aid) {
        $stmt->execute([$compra_id, $aid, $precio_unitario]);
    }

    $pdo->commit();

    $_SESSION['compra_exitosa'] = $compra_id;
    redirect("../../public/ticket.php?compra_id=$compra_id");

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    redirect("../../public/compra.php?pelicula_id=$pelicula_id");
}
