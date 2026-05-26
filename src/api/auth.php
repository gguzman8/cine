<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';

function api_register(PDO $pdo): void {
    $data = json_decode(file_get_contents('php://input'), true);

    $nombre   = trim($data['nombre'] ?? '');
    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if ($nombre === '' || $email === '' || $password === '') {
        json_error('Todos los campos son obligatorios.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Correo inválido.');
    }
    if (strlen($password) < 6) {
        json_error('La contraseña debe tener al menos 6 caracteres.');
    }

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_error('El correo ya está registrado.', 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)'
    );
    $stmt->execute([$nombre, $email, $hash]);
    $usuario_id = (int) $pdo->lastInsertId();

    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare(
        'INSERT INTO api_tokens (usuario_id, token) VALUES (?, ?)'
    );
    $stmt->execute([$usuario_id, $token]);

    $pdo->commit();

    json_created([
        'token' => $token,
        'usuario' => [
            'id'     => $usuario_id,
            'nombre' => $nombre,
            'email'  => $email,
            'rol'    => 'cliente',
        ],
    ], 'Usuario registrado');

    // Si la transacción falla
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

function api_login(PDO $pdo): void {
    $data = json_decode(file_get_contents('php://input'), true);

    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if ($email === '' || $password === '') {
        json_error('Ingresa correo y contraseña.');
    }

    $stmt = $pdo->prepare(
        'SELECT id, nombre, email, password_hash, rol FROM usuarios WHERE email = ?'
    );
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        json_error('Credenciales inválidas.', 401);
    }

    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare(
        'INSERT INTO api_tokens (usuario_id, token) VALUES (?, ?)'
    );
    $stmt->execute([$usuario['id'], $token]);

    json_success([
        'token' => $token,
        'usuario' => [
            'id'     => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email'  => $usuario['email'],
            'rol'    => $usuario['rol'],
        ],
    ], 'Inicio de sesión exitoso');
}
