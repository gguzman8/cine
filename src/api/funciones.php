<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';

function api_obtener_funcion(PDO $pdo, int $id): void {
    limpiar_funciones_expiradas($pdo);
    $stmt = $pdo->prepare(
        'SELECT f.id, f.horario, f.sala, p.id AS pelicula_id, p.titulo, p.precio
         FROM funciones f
         JOIN peliculas p ON p.id = f.pelicula_id
         WHERE f.id = ? AND f.expirada = FALSE'
    );
    $stmt->execute([$id]);
    $funcion = $stmt->fetch();

    if (!$funcion) {
        json_error('Función no encontrada.', 404);
    }

    $stmt = $pdo->prepare(
        'SELECT id, fila, numero, disponible
         FROM asientos
         WHERE funcion_id = ?
         ORDER BY fila, numero'
    );
    $stmt->execute([$id]);

    $funcion['asientos'] = $stmt->fetchAll();
    json_success($funcion);
}
