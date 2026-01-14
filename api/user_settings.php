<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/api_bootstrap.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';

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

$cookieUserId = ih_get_user_id_cookie();
$user = $cookieUserId ? ih_get_user($cookieUserId) : null;
if (!$user) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'Kein Account gefunden.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$isAdmin = is_admin($cookieUserId);
if ((int)($user['is_banned'] ?? 0) === 1 && !$isAdmin) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Account gesperrt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$requested = $payload['ttl_seconds'] ?? $payload['ttl'] ?? null;
$ttlSeconds = ih_normalize_ttl_selection($requested, $isAdmin);
if ($ttlSeconds === null && $requested !== 'unlimited') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'TTL nicht erlaubt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

if ($requested === 'unlimited' && !$isAdmin) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'TTL nicht erlaubt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

ih_save_user_ttl($cookieUserId, $ttlSeconds);
log_msg('info', 'user ttl updated', [
    'user_id' => $cookieUserId,
    'ttl_seconds' => $ttlSeconds,
]);

echo json_encode([
    'ok' => true,
    'ttl_seconds' => $ttlSeconds,
]);
