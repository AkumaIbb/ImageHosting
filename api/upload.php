<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/api_bootstrap.php';
require_once __DIR__ . '/../lib/uploads.php';
require_once __DIR__ . '/../lib/base_url.php';
require_once __DIR__ . '/../lib/shortcodes.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';

const IH_MAX_FILES_PER_REQUEST = 20;
const IH_MAX_BYTES_PER_FILE = 10485760;
const IH_MAX_BYTES_TOTAL = 52428800;
const IH_RATE_LIMIT_MAX = 60;
const IH_RATE_LIMIT_WINDOW = 600;

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
$fileCount = 0;
$totalSize = 0;
foreach ($files as $entry) {
    if (($entry['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        continue;
    }
    $fileCount++;
    $totalSize += (int)($entry['size'] ?? 0);
}

if ($fileCount > IH_MAX_FILES_PER_REQUEST) {
    http_response_code(413);
    log_msg('warning', 'upload blocked too many files', [
        'count' => $fileCount,
        'limit' => IH_MAX_FILES_PER_REQUEST,
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'too_many_files',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
if ($contentLength > IH_MAX_BYTES_TOTAL || $totalSize > IH_MAX_BYTES_TOTAL) {
    http_response_code(413);
    log_msg('warning', 'upload blocked payload too large', [
        'content_length' => $contentLength,
        'total_size' => $totalSize,
        'limit' => IH_MAX_BYTES_TOTAL,
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'payload_too_large',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$rateKey = 'upload_' . (string)($cookieUserId ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
if (!ih_rate_limit_allow($rateKey, IH_RATE_LIMIT_MAX, IH_RATE_LIMIT_WINDOW)) {
    http_response_code(429);
    log_msg('warning', 'upload rate limited', [
        'key' => $rateKey,
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'rate_limited',
        'request_id' => api_request_id(),
    ]);
    exit;
}

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
    $defaultTtl = ih_get_default_ttl_seconds();
    $expiresAt = $defaultTtl === null ? null : $now + $defaultTtl;
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
        if (($file['error'] ?? null) === UPLOAD_ERR_INI_SIZE || ($file['error'] ?? null) === UPLOAD_ERR_FORM_SIZE) {
            http_response_code(413);
            log_msg('warning', 'upload blocked file too large', [
                'name' => $file['name'] ?? '',
                'size' => $file['size'] ?? 0,
                'limit' => IH_MAX_BYTES_PER_FILE,
            ]);
            echo json_encode([
                'ok' => false,
                'error' => 'file_too_large',
                'request_id' => api_request_id(),
            ]);
            exit;
        }
        $errors[] = $file['name'] ?? 'Unbekannte Datei';
        continue;
    }
    if ((int)($file['size'] ?? 0) > IH_MAX_BYTES_PER_FILE) {
        http_response_code(413);
        log_msg('warning', 'upload blocked file too large', [
            'name' => $file['name'] ?? '',
            'size' => $file['size'] ?? 0,
            'limit' => IH_MAX_BYTES_PER_FILE,
        ]);
        echo json_encode([
            'ok' => false,
            'error' => 'file_too_large',
            'request_id' => api_request_id(),
        ]);
        exit;
    }
    $mime = ih_is_image_file($file['tmp_name']);
    if (!$mime) {
        $detected = ih_detect_mime_type($file['tmp_name']);
        log_msg('warning', 'upload blocked unsupported mime', [
            'name' => $file['name'] ?? '',
            'client_type' => $file['type'] ?? '',
            'detected_mime' => $detected,
        ]);
        $errors[] = $file['name'] ?? 'Unbekannte Datei';
        continue;
    }
    $extension = ih_extension_for_mime($mime);
    if (!$extension) {
        log_msg('warning', 'upload blocked missing extension mapping', [
            'mime' => $mime,
            'name' => $file['name'] ?? '',
        ]);
        $errors[] = $file['name'] ?? 'Unbekannte Datei';
        continue;
    }
    $fileId = ih_generate_id();
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
