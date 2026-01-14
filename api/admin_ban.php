<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/api_bootstrap.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/db.php';

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
if (!is_admin($cookieUserId)) {
    http_response_code(403);
    log_msg('warning', 'admin ban auth failed', [
        'user_id' => $cookieUserId,
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'admin_auth_required',
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

$userId = $payload['user_id'] ?? null;
$banned = $payload['banned'] ?? null;
if (!is_string($userId) || !preg_match('/^[0-9A-Za-z]{12,16}$/', $userId)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Ungültige User-ID.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$isBanned = filter_var($banned, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if ($isBanned === null) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Ungültiger Ban-Status.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$pdo = ih_db();
$stmt = $pdo->prepare('UPDATE users SET is_banned = :is_banned WHERE user_id = :user_id');
$stmt->execute([
    ':is_banned' => $isBanned ? 1 : 0,
    ':user_id' => $userId,
]);

log_msg('info', 'admin ban update', [
    'target_user_id' => $userId,
    'banned' => $isBanned,
    'admin_user_id' => $cookieUserId,
]);

echo json_encode([
    'ok' => true,
    'user_id' => $userId,
    'is_banned' => $isBanned,
]);
