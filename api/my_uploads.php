<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/api_bootstrap.php';
require_once __DIR__ . '/../lib/users.php';
require_once __DIR__ . '/../lib/uploads.php';
require_once __DIR__ . '/../lib/base_url.php';
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
if (!$user) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'Kein Account gefunden.',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(6, (int)($_GET['per_page'] ?? 24)));
$offset = ($page - 1) * $perPage;

$pdo = ih_db();
$countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM uploads WHERE user_id = :user_id AND (expires_at IS NULL OR expires_at > :now)');
$countStmt->execute([':user_id' => $cookieUserId, ':now' => time()]);
$total = (int)($countStmt->fetch()['total'] ?? 0);

$stmt = $pdo->prepare('SELECT upload_id, created_at, expires_at, type, short_code, preview_file, file_count FROM uploads WHERE user_id = :user_id AND (expires_at IS NULL OR expires_at > :now) ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':user_id', $cookieUserId, PDO::PARAM_STR);
$stmt->bindValue(':now', time(), PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$items = [];
foreach ($rows as $row) {
    $uploadId = $row['upload_id'];
    $shortCode = $row['short_code'] ?? null;
    $preview = null;
    if (!empty($row['preview_file'])) {
        $preview = ih_public_file_url($uploadId, $row['preview_file']);
    }
    $items[] = [
        'upload_id' => $uploadId,
        'created_at' => (int)$row['created_at'],
        'expires_at' => $row['expires_at'] !== null ? (int)$row['expires_at'] : null,
        'type' => $row['type'],
        'short_code' => $shortCode,
        'public_url' => $shortCode ? '/v.php?id=' . $shortCode : null,
        'short_url' => $shortCode ? base_url() . '/?id=' . $shortCode : null,
        'manage_url' => '/u.php?id=' . $uploadId,
        'preview_url' => $preview,
        'file_count' => (int)($row['file_count'] ?? 0),
    ];
}

echo json_encode([
    'ok' => true,
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'items' => $items,
]);
