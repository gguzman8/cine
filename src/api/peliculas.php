<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';

function api_listar_peliculas(PDO $pdo): void {
    $stmt = $pdo->query(
        'SELECT p.id, p.titulo, p.sinopsis, p.poster, p.precio,
                (SELECT COUNT(*) FROM funciones f WHERE f.pelicula_id = p.id) AS funciones_count
         FROM peliculas p
         ORDER BY p.titulo'
    );
    json_success($stmt->fetchAll());
}

function api_obtener_pelicula(PDO $pdo, int $id): void {
    $stmt = $pdo->prepare(
        'SELECT id, titulo, sinopsis, poster, precio FROM peliculas WHERE id = ?'
    );
    $stmt->execute([$id]);
    $pelicula = $stmt->fetch();

    if (!$pelicula) {
        json_error('Película no encontrada.', 404);
    }

    $stmt = $pdo->prepare(
        'SELECT f.id, f.horario, f.sala,
                (SELECT COUNT(*) FROM asientos a WHERE a.funcion_id = f.id AND a.disponible = TRUE) AS asientos_libres
         FROM funciones f
         WHERE f.pelicula_id = ?
         ORDER BY f.horario'
    );
    $stmt->execute([$id]);

    $pelicula['funciones'] = $stmt->fetchAll();
    json_success($pelicula);
}
