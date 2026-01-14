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
    'message' => 'Upload-Endpunkt ist vorbereitet.',
    'next' => 'Datei-Handling und Speicherung in storage/ implementieren.',
]);
