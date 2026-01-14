<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/uploads.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Nur POST erlaubt.',
    ]);
    exit;
}

ih_ensure_dirs();

$uploadId = ih_sanitize_id($_POST['upload_id'] ?? null);
$files = ih_collect_files($_FILES);

if (!$files) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Keine Dateien gefunden.',
    ]);
    exit;
}

$upload = null;
if ($uploadId) {
    $upload = ih_load_upload($uploadId);
    if (!$upload) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'error' => 'Upload nicht gefunden.',
        ]);
        exit;
    }
} else {
    $uploadId = ih_generate_id();
    $upload = [
        'id' => $uploadId,
        'created_at' => time(),
        'updated_at' => time(),
        'type' => 'single',
        'files' => [],
    ];
}

$uploadDir = ih_storage_dir() . '/' . $uploadId;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$errors = [];
$added = 0;
foreach ($files as $file) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = $file['name'] ?? 'Unbekannte Datei';
        continue;
    }
    $mime = ih_is_image_file($file['tmp_name']);
    if (!$mime) {
        $errors[] = $file['name'] ?? 'Unbekannte Datei';
        continue;
    }
    $fileId = ih_generate_id();
    $extension = ih_guess_extension($file['name'] ?? '', $mime);
    $filename = $fileId . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $errors[] = $file['name'] ?? 'Unbekannte Datei';
        continue;
    }
    $upload['files'][] = [
        'id' => $fileId,
        'filename' => $filename,
        'original' => $file['name'] ?? $filename,
        'mime' => $mime,
        'size' => $file['size'] ?? 0,
    ];
    $added++;
}

if ($added === 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Keine gültigen Bilddateien gefunden.',
    ]);
    exit;
}

$upload['type'] = count($upload['files']) > 1 ? 'album' : 'single';
ih_save_upload($upload);

echo json_encode([
    'ok' => true,
    'upload_id' => $uploadId,
    'type' => $upload['type'],
    'public_url' => '/v.php?id=' . $uploadId,
    'manage_url' => '/u.php?id=' . $uploadId,
    'added' => $added,
    'skipped' => count($errors),
]);
