<?php

function json_output($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): void {
    json_output(['error' => $message], $status);
}

function json_success($data = null, string $message = 'OK'): void {
    json_output(['message' => $message, 'data' => $data]);
}

function json_created($data = null, string $message = 'Creado'): void {
    json_output(['message' => $message, 'data' => $data], 201);
}
