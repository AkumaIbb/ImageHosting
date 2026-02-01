<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/api_bootstrap.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Nur GET erlaubt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$cookieUserId = ih_get_user_id_cookie();
$user = $cookieUserId ? ih_get_user($cookieUserId) : null;
$isAdmin = $user ? is_admin($cookieUserId) : false;

$ttlSeconds = null;
$isBanned = false;
$userId = null;
if ($user) {
    $userId = $cookieUserId;
    $ttlSeconds = ih_effective_ttl_seconds($user['ttl_seconds'] !== null ? (int)$user['ttl_seconds'] : null, $isAdmin);
    $isBanned = (int)($user['is_banned'] ?? 0) === 1;
}

echo json_encode([
    'ok' => true,
    'user_id' => $userId,
    'is_admin' => $isAdmin,
    'ttl_options' => ih_ttl_options_payload($isAdmin),
    'ttl_seconds' => $ttlSeconds,
    'is_banned' => $isBanned,
]);
