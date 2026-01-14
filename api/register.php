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

$cookieUserId = ih_get_user_id_cookie();
if ($cookieUserId) {
    $existing = ih_get_user($cookieUserId);
    if ($existing) {
        echo json_encode([
            'ok' => true,
            'user_id' => $cookieUserId,
            'is_admin' => is_admin($cookieUserId),
        ]);
        exit;
    }
}

$userId = ih_create_user();
setcookie('ih_uid', $userId, [
    'expires' => time() + 31536000,
    'path' => '/',
    'samesite' => 'Lax',
    'httponly' => true,
]);

log_msg('info', 'user registered', ['user_id' => $userId]);

echo json_encode([
    'ok' => true,
    'user_id' => $userId,
]);
