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

function requerir_login(): void {
    if (!esta_logueado()) {
        header('Location: login.php');
        exit;
    }
}
