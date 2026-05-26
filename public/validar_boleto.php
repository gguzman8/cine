<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/session.php';

$compra_id = (int) ($_GET['compra_id'] ?? 0);

if ($compra_id <= 0) {
    echo json_encode(['valido' => false]);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM compras WHERE id = ?');
$stmt->execute([$compra_id]);
$existe = $stmt->fetch() ? true : false;

header('Content-Type: application/json');
echo json_encode(['valido' => $existe]);
