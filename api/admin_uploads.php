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
if (!is_admin($cookieUserId)) {
    http_response_code(403);
    log_msg('warning', 'admin uploads auth failed', [
        'user_id' => $cookieUserId,
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'admin_auth_required',
        'request_id' => api_request_id(),
    ]);
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(6, (int)($_GET['per_page'] ?? 24)));
$offset = ($page - 1) * $perPage;
$filterUserId = null;
if (!empty($_GET['user_id'])) {
    $candidate = (string)$_GET['user_id'];
    $filterUserId = preg_match('/^[0-9A-Za-z]{12,16}$/', $candidate) ? $candidate : null;
}

$pdo = ih_db();
$params = [];
$where = '';
if ($filterUserId) {
    $where = 'WHERE uploads.user_id = :user_id';
    $params[':user_id'] = $filterUserId;
}

$countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM uploads ' . $where);
$countStmt->execute($params);
$total = (int)($countStmt->fetch()['total'] ?? 0);

$query = 'SELECT uploads.upload_id, uploads.user_id, uploads.created_at, uploads.expires_at, uploads.type, uploads.short_code, uploads.preview_file, uploads.file_count, users.is_banned
    FROM uploads
    LEFT JOIN users ON users.user_id = uploads.user_id
    ' . $where . '
    ORDER BY uploads.created_at DESC
    LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
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
        'user_id' => $row['user_id'],
        'created_at' => (int)$row['created_at'],
        'expires_at' => $row['expires_at'] !== null ? (int)$row['expires_at'] : null,
        'type' => $row['type'],
        'short_code' => $shortCode,
        'public_url' => $shortCode ? '/v.php?id=' . $shortCode : null,
        'short_url' => $shortCode ? base_url() . '/?id=' . $shortCode : null,
        'manage_url' => '/u.php?id=' . $uploadId,
        'preview_url' => $preview,
        'file_count' => (int)($row['file_count'] ?? 0),
        'is_banned' => (int)($row['is_banned'] ?? 0) === 1,
    ];
}

echo json_encode([
    'ok' => true,
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'items' => $items,
]);
