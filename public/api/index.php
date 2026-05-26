<?php

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/api/response.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['PATH_INFO'] ?? '/';
$uri    = rtrim($uri, '/') ?: '/';
$parts  = explode('/', trim($uri, '/'));

require_once __DIR__ . '/../../src/api/auth.php';
require_once __DIR__ . '/../../src/api/peliculas.php';
require_once __DIR__ . '/../../src/api/funciones.php';
require_once __DIR__ . '/../../src/api/compras.php';

try {
    switch ($parts[0] ?? '') {

        // ─── Auth ───────────────────────────────
        case 'auth':
            if ($method === 'POST' && ($parts[1] ?? '') === 'register') {
                api_register($pdo);
            } elseif ($method === 'POST' && ($parts[1] ?? '') === 'login') {
                api_login($pdo);
            }
            json_error('Endpoint de auth no válido.', 404);
            break;

        // ─── Películas ──────────────────────────
        case 'peliculas':
            if ($method === 'GET' && empty($parts[1])) {
                api_listar_peliculas($pdo);
            } elseif ($method === 'GET' && $parts[1] && ctype_digit($parts[1])) {
                api_obtener_pelicula($pdo, (int) $parts[1]);
            }
            json_error('Endpoint de películas no válido.', 404);
            break;

        // ─── Funciones ──────────────────────────
        case 'funciones':
            if ($method === 'GET' && !empty($parts[1]) && ctype_digit($parts[1])) {
                api_obtener_funcion($pdo, (int) $parts[1]);
            }
            json_error('Endpoint de funciones no válido.', 404);
            break;

        // ─── Compras ────────────────────────────
        case 'compras':
            if ($method === 'POST' && empty($parts[1])) {
                api_crear_compra($pdo);
            } elseif ($method === 'GET' && empty($parts[1])) {
                api_listar_compras($pdo);
            } elseif ($method === 'GET' && !empty($parts[1]) && ctype_digit($parts[1])) {
                api_obtener_compra($pdo, (int) $parts[1]);
            }
            json_error('Endpoint de compras no válido.', 404);
            break;

        default:
            json_error('Endpoint no encontrado.', 404);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_error('Error interno del servidor.', 500);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_error('Error interno del servidor.', 500);
}
