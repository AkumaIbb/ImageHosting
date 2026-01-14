<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Nur POST erlaubt.',
    ]);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'message' => 'Album-Erstellung ist vorbereitet.',
    'next' => 'Album-Metadaten in data/ speichern.',
]);
