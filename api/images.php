<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Nur GET erlaubt.',
    ]);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'message' => 'Bildliste ist vorbereitet.',
    'images' => [],
    'next' => 'Metadaten aus data/ laden und sortieren.',
]);
