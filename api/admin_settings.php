<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/api_bootstrap.php';
require_once __DIR__ . '/../lib/admin.php';
require_once __DIR__ . '/../lib/settings.php';
require_once __DIR__ . '/../lib/users.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Nur GET oder POST erlaubt.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$cookieUserId = ih_get_user_id_cookie();
if (!is_admin($cookieUserId)) {
    http_response_code(403);
    log_msg('warning', 'admin settings auth failed', [
        'user_id' => $cookieUserId,
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'admin_auth_required',
        'request_id' => api_request_id(),
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $defaultSeconds = ih_get_default_ttl_seconds();
    $defaultHours = $defaultSeconds === null ? 0 : (int)round($defaultSeconds / 3600);
    echo json_encode([
        'ok' => true,
        'default_ttl_seconds' => $defaultSeconds,
        'default_ttl_hours' => $defaultHours,
    ]);
    exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
$hours = $payload['default_ttl_hours'] ?? null;
if (!is_int($hours) && !(is_string($hours) && ctype_digit($hours))) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid default_ttl_hours.',
        'request_id' => api_request_id(),
    ]);
    exit;
}
$hoursInt = (int)$hours;
if ($hoursInt < 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid default_ttl_hours.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$seconds = $hoursInt === 0 ? null : $hoursInt * 3600;
if ($seconds !== null && $seconds <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid default_ttl_hours.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

ih_set_default_ttl_seconds($seconds);
log_msg('info', 'default ttl updated', [
    'user_id' => $cookieUserId,
    'ttl_seconds' => $seconds,
]);

echo json_encode([
    'ok' => true,
    'default_ttl_seconds' => $seconds,
    'default_ttl_hours' => $hoursInt,
]);
