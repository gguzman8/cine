<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/functions.php';

limpiar_funciones_expiradas($pdo);

$count = $pdo->exec(
    "UPDATE funciones SET expirada = TRUE WHERE horario <= NOW() AND expirada = FALSE"
);

echo date('Y-m-d H:i:s') . " — $count funciones marcadas como expiradas.\n";
