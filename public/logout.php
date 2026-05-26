<?php
require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/functions.php';

$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

redirect('index.php');
