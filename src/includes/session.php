<?php

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

function esta_logueado(): bool {
    return !empty($_SESSION['usuario_id']);
}

function es_admin(): bool {
    return ($_SESSION['usuario_rol'] ?? '') === 'admin';
}

function es_staff(): bool {
    return ($_SESSION['usuario_rol'] ?? '') === 'vendedor';
}

function requerir_login(): void {
    if (!esta_logueado()) {
        header('Location: /login.php');
        exit;
    }
}

function requerir_rol(string ...$roles): void {
    requerir_login();
    if (!in_array($_SESSION['usuario_rol'] ?? '', $roles, true)) {
        header('Location: /index.php');
        exit;
    }
}
