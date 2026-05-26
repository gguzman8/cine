<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';
requerir_rol('cliente', 'vendedor');

$funcion_id = (int) ($_GET['funcion_id'] ?? 0);
header('Content-Type: application/json');

if ($funcion_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de función inválido']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, fila, numero, disponible FROM asientos WHERE funcion_id = ? ORDER BY fila, numero');
$stmt->execute([$funcion_id]);
$asientos = $stmt->fetchAll();

echo json_encode($asientos);
