<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/middleware.php';

function api_crear_compra(PDO $pdo): void {
    $usuario = requerir_autenticacion($pdo);

    limpiar_funciones_expiradas($pdo);

    $data = json_decode(file_get_contents('php://input'), true);
    $funcion_id = (int) ($data['funcion_id'] ?? 0);
    $cantidad   = (int) ($data['cantidad'] ?? 0);

    if ($funcion_id <= 0 || $cantidad <= 0 || $cantidad > 10) {
        json_error('Datos inválidos. funcion_id y cantidad (1-10) requeridos.');
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

        if ($funcion['expirada']) {
            throw new Exception('Esta función ya ha finalizado.');
        }

        $asientos = $pdo->prepare(
            'SELECT id, fila, numero FROM asientos
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
        $stmt->execute([$usuario['id'], $funcion_id, $total]);
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

        json_created([
            'compra_id' => $compra_id,
            'total'     => (float) $total,
            'pelicula'  => $funcion['titulo'],
            'horario'   => $funcion['horario'],
            'sala'      => $funcion['sala'],
            'asientos'  => array_map(fn($a) => $a['fila'] . $a['numero'], $asientos),
        ], 'Compra exitosa');

    } catch (Exception $e) {
        $pdo->rollBack();
        json_error($e->getMessage());
    }
}

function api_listar_compras(PDO $pdo): void {
    $usuario = requerir_autenticacion($pdo);

    $stmt = $pdo->prepare(
        'SELECT c.id, c.total, c.created_at, p.titulo, f.horario, f.sala
         FROM compras c
         JOIN funciones f ON f.id = c.funcion_id
         JOIN peliculas p ON p.id = f.pelicula_id
         WHERE c.usuario_id = ?
         ORDER BY c.created_at DESC'
    );
    $stmt->execute([$usuario['id']]);
    json_success($stmt->fetchAll());
}

function api_obtener_compra(PDO $pdo, int $id): void {
    $usuario = requerir_autenticacion($pdo);

    $stmt = $pdo->prepare(
        'SELECT c.id, c.total, c.created_at, p.titulo, f.horario, f.sala
         FROM compras c
         JOIN funciones f ON f.id = c.funcion_id
         JOIN peliculas p ON p.id = f.pelicula_id
         WHERE c.id = ? AND c.usuario_id = ?'
    );
    $stmt->execute([$id, $usuario['id']]);
    $compra = $stmt->fetch();

    if (!$compra) {
        json_error('Compra no encontrada.', 404);
    }

    $stmt = $pdo->prepare(
        'SELECT a.fila, a.numero, d.precio
         FROM detalle_compra d
         JOIN asientos a ON a.id = d.asiento_id
         WHERE d.compra_id = ?'
    );
    $stmt->execute([$id]);

    $compra['asientos'] = $stmt->fetchAll();
    json_success($compra);
}
