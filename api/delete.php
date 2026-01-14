<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/api_bootstrap.php';
require_once __DIR__ . '/../lib/uploads.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/shortcodes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Nur POST erlaubt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $payload = json_decode((string)file_get_contents('php://input'), true) ?? [];
} else {
    $payload = $_POST;
}

$uploadId = ih_sanitize_id($payload['upload_id'] ?? null);
$fileId = $payload['file_id'] ?? $payload['id'] ?? null;
$deleteUpload = filter_var($payload['delete_upload'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!$uploadId || (!$fileId && !$deleteUpload)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Upload-ID oder Datei-ID fehlt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$upload = ih_load_upload($uploadId);
if (!$upload) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => 'Upload nicht gefunden.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$cookieUserId = ih_get_user_id_cookie();
$user = $cookieUserId ? ih_get_user($cookieUserId) : null;
$isAdmin = $user ? is_admin($cookieUserId) : false;
if ($user && (int)($user['is_banned'] ?? 0) === 1 && !$isAdmin) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Account gesperrt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$ownerId = $upload['user_id'] ?? null;
if ($ownerId && !$isAdmin && $ownerId !== $cookieUserId) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Nicht autorisiert.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

if ($deleteUpload) {
    ih_delete_upload($upload);
    short_delete_by_upload($uploadId);
    echo json_encode([
        'ok' => true,
        'remaining' => 0,
        'type' => 'single',
    ]);
    exit;
}

$remaining = [];
$deleted = false;
foreach ($upload['files'] as $file) {
    if ($file['id'] === $fileId) {
        $path = ih_storage_dir() . '/' . $uploadId . '/' . $file['filename'];
        if (is_file($path)) {
            unlink($path);
        }
        $deleted = true;
        continue;
    }
    $remaining[] = $file;
}

if (!$deleted) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => 'Datei nicht gefunden.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

if (!$remaining) {
    ih_delete_upload($upload);
    if ($uploadId) {
        short_delete_by_upload($uploadId);
    }
    echo json_encode([
        'ok' => true,
        'remaining' => 0,
        'type' => 'single',
    ]);
    exit;
}

$upload['files'] = $remaining;
$upload['type'] = count($remaining) > 1 ? 'album' : 'single';
ih_save_upload($upload);

echo json_encode([
    'ok' => true,
    'remaining' => count($remaining),
    'type' => $upload['type'],
]);
