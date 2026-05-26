<?php

define('MATINEE_DESCUENTO', 30);

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificar_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function redirect_abs(string $url): void {
    header("Location: /$url");
    exit;
}

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function es_matinee_horario(string $horario): bool {
    $ts = strtotime($horario);
    $dia = (int) date('N', $ts);
    $hora = (int) date('G', $ts);
    return $dia <= 5 && $hora < 12;
}

function precio_con_matinee(float $precio_base, bool $es_matinee): float {
    if ($es_matinee && (int) date('G') < 12) {
        return round($precio_base * (1 - MATINEE_DESCUENTO / 100), 2);
    }
    return $precio_base;
}

function validar_cupon(PDO $pdo, string $codigo): ?array {
    $stmt = $pdo->prepare('SELECT * FROM cupones WHERE codigo = ? AND activo = TRUE AND usos_actuales < usos_maximos');
    $stmt->execute([trim($codigo)]);
    $cupon = $stmt->fetch();
    return $cupon ?: null;
}

function aplicar_cupon(PDO $pdo, int $cupon_id): void {
    $pdo->prepare('UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE id = ?')
        ->execute([$cupon_id]);
}

function limpiar_funciones_expiradas(PDO $pdo): void {
    $pdo->exec("UPDATE funciones SET expirada = TRUE WHERE horario <= NOW() AND expirada = FALSE");
}
