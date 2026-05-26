<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';

function obtener_usuario_por_token(PDO $pdo): ?array {
    $header = '';

    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if ($header === '') {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return null;
    }

    $token = $m[1];

    $stmt = $pdo->prepare(
        'SELECT u.id, u.nombre, u.email, u.rol
         FROM api_tokens t
         JOIN usuarios u ON u.id = t.usuario_id
         WHERE t.token = ?'
    );
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

function requerir_autenticacion(PDO $pdo): array {
    $usuario = obtener_usuario_por_token($pdo);
    if (!$usuario) {
        json_error('No autenticado. Envía Authorization: Bearer <token>', 401);
    }
    return $usuario;
}
