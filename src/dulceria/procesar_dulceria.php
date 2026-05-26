<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
requerir_rol('cliente', 'vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dulceria.php');
}

if (!verificar_csrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_dulceria'] = 'Token CSRF inválido.';
    redirect('/dulceria.php');
}

$cantidades = $_POST['cantidad'] ?? [];
$items = [];

foreach ($cantidades as $id => $qty) {
    $qty = (int) $qty;
    if ($qty > 0) {
        $items[(int) $id] = $qty;
    }
}

if (empty($items)) {
    $_SESSION['error_dulceria'] = 'Selecciona al menos un producto.';
    redirect('/dulceria.php');
}

try {
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($items), '?'));
    $stmt = $pdo->prepare("SELECT id, nombre, precio FROM productos WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($items));
    $productos = $stmt->fetchAll();

    if (count($productos) !== count($items)) {
        throw new Exception('Algunos productos no existen.');
    }

    $total = 0;
    $detalle = [];
    foreach ($productos as $p) {
        $qty = $items[(int)$p['id']];
        $subtotal = (float)$p['precio'] * $qty;
        $total += $subtotal;
        $detalle[] = [$p['id'], $qty, (float)$p['precio']];
    }

    if (es_staff()) {
        $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
        if ($cliente_nombre === '') {
            throw new Exception('Debes ingresar el nombre del cliente.');
        }
        $vendedor_nombre = $_SESSION['usuario_nombre'];
    } else {
        $cliente_nombre = $_SESSION['usuario_nombre'];
        $vendedor_nombre = 'Sistema';
    }

    $para_llevar = !empty($_POST['para_llevar']);
    $boleto_id = null;
    if ($para_llevar) {
        $boleto_id = (int) ($_POST['boleto_id'] ?? 0);
        if ($boleto_id <= 0) {
            throw new Exception('Ingresa el número de boleto para la entrega en sala.');
        }
        $stmt = $pdo->prepare('SELECT id FROM compras WHERE id = ?');
        $stmt->execute([$boleto_id]);
        if (!$stmt->fetch()) {
            throw new Exception('El número de boleto no existe.');
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO dulceria_compras (usuario_id, cliente_nombre, vendedor_nombre, para_llevar, boleto_id, total) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$_SESSION['usuario_id'], $cliente_nombre, $vendedor_nombre, $para_llevar ? 1 : 0, $boleto_id, $total]);
    $compra_id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        'INSERT INTO dulceria_detalle (compra_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)'
    );
    foreach ($detalle as $d) {
        $stmt->execute([$compra_id, $d[0], $d[1], $d[2]]);
    }

    $pdo->commit();

    $_SESSION['compra_dulce_exitosa'] = $compra_id;
    redirect("/dulceria_recibo.php?compra_id=$compra_id");

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_dulceria'] = $e->getMessage();
    redirect('/dulceria.php');
}
