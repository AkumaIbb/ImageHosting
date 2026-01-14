<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/api_bootstrap.php';
require_once __DIR__ . '/../lib/uploads.php';
require_once __DIR__ . '/../lib/base_url.php';
require_once __DIR__ . '/../lib/shortcodes.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';

log_msg('info', 'upload request start', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? '',
]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    log_msg('warning', 'upload invalid method', [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'Nur POST erlaubt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

ih_ensure_dirs();

$cookieUserId = ih_get_user_id_cookie();
$user = $cookieUserId ? ih_get_user($cookieUserId) : null;
$isAdmin = $user ? is_admin($cookieUserId) : false;
if ($user && (int)($user['is_banned'] ?? 0) === 1 && !$isAdmin) {
    http_response_code(403);
    log_msg('warning', 'upload blocked for banned user', [
        'user_id' => $cookieUserId,
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'Account gesperrt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$uploadId = ih_sanitize_id($_POST['upload_id'] ?? null);
$files = ih_collect_files($_FILES);
$fileSummary = [];
foreach ($_FILES as $key => $entry) {
    if (!is_array($entry)) {
        continue;
    }
    if (is_array($entry['name'] ?? null)) {
        foreach ($entry['name'] as $index => $name) {
            $fileSummary[] = [
                'field' => $key,
                'name' => $name,
                'size' => $entry['size'][$index] ?? 0,
                'error' => $entry['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            ];
        }
    } else {
        $fileSummary[] = [
            'field' => $key,
            'name' => $entry['name'] ?? '',
            'size' => $entry['size'] ?? 0,
            'error' => $entry['error'] ?? UPLOAD_ERR_NO_FILE,
        ];
    }
}
log_msg('info', 'upload files received', [
    'files' => $fileSummary,
]);

if (!$files) {
    http_response_code(400);
    log_msg('warning', 'upload missing files');
    echo json_encode([
        'ok' => false,
        'error' => 'Keine Dateien gefunden.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$upload = null;
if ($uploadId) {
    $upload = ih_load_upload($uploadId);
    if (!$upload) {
        http_response_code(404);
        log_msg('warning', 'upload not found', [
            'upload_id' => $uploadId,
        ]);
        echo json_encode([
            'ok' => false,
            'error' => 'Upload nicht gefunden.',
            'request_id' => api_request_id(),
        ]);
        exit;
    }
    $ownerId = $upload['user_id'] ?? null;
    if ($ownerId && !$isAdmin && $ownerId !== $cookieUserId) {
        http_response_code(403);
        log_msg('warning', 'upload ownership mismatch', [
            'upload_id' => $uploadId,
            'owner_id' => $ownerId,
            'user_id' => $cookieUserId,
        ]);
        echo json_encode([
            'ok' => false,
            'error' => 'Nicht autorisiert.',
            'request_id' => api_request_id(),
        ]);
        exit;
    }
} else {
    $uploadId = ih_generate_id();
    $now = time();
    $expiresAt = $now + 172800;
    if ($user) {
        $ttlSeconds = ih_effective_ttl_seconds($user['ttl_seconds'] !== null ? (int)$user['ttl_seconds'] : null, $isAdmin);
        if ($ttlSeconds === null) {
            $expiresAt = null;
        } else {
            $expiresAt = $now + $ttlSeconds;
        }
    }
    $upload = [
        'id' => $uploadId,
        'created_at' => $now,
        'updated_at' => $now,
        'type' => 'single',
        'files' => [],
        'user_id' => $user ? $cookieUserId : null,
        'expires_at' => $expiresAt,
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
    log_msg('warning', 'upload no valid images', [
        'errors' => $errors,
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'Keine gültigen Bilddateien gefunden.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$upload['type'] = count($upload['files']) > 1 ? 'album' : 'single';
$expiresAt = $upload['expires_at'] ?? (($upload['created_at'] ?? time()) + 172800);
$shortCode = null;
if (!empty($upload['short_code']) && short_is_valid_code($upload['short_code'])) {
    $existing = short_resolve($upload['short_code']);
    if ($existing && ($existing['upload_id'] ?? '') === $uploadId && ($existing['expires_at'] ?? 0) >= time()) {
        $shortCode = $upload['short_code'];
    }
}
if (!$shortCode) {
    $shortExpiry = $expiresAt ?? IH_SHORTCODE_MAX_EXPIRES;
    $shortCode = short_create($uploadId, $shortExpiry);
    $upload['short_code'] = $shortCode;
}
ih_save_upload($upload);
$publicUrl = '/v.php?id=' . $shortCode;
$manageUrl = '/u.php?id=' . $uploadId;
$shortUrl = base_url() . '/?id=' . $shortCode;

log_msg('info', 'upload success', [
    'upload_id' => $uploadId,
    'type' => $upload['type'],
    'added' => $added,
    'skipped' => count($errors),
]);

echo json_encode([
    'ok' => true,
    'upload_id' => $uploadId,
    'type' => $upload['type'],
    'public_url' => $publicUrl,
    'manage_url' => $manageUrl,
    'short_code' => $shortCode,
    'short_url' => $shortUrl,
    'user_id_present' => (bool)$user,
]);
