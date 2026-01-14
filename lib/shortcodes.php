<?php
declare(strict_types=1);

require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/db.php';

function short_init(): PDO
{
    static $initialized = false;
    $pdo = ih_db();
    if ($initialized) {
        return $pdo;
    }
    $initialized = true;

    $count = $pdo->query('SELECT COUNT(*) AS total FROM shortcodes')->fetch();
    $existing = (int)($count['total'] ?? 0);
    if ($existing > 0) {
        return $pdo;
    }

    $legacyPath = dirname(__DIR__) . '/storage/shortcodes.sqlite';
    if (!is_file($legacyPath)) {
        return $pdo;
    }

    $legacy = new PDO('sqlite:' . $legacyPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $columns = $legacy->query('PRAGMA table_info(shortcodes)')->fetchAll();
    $columnNames = array_map(static fn(array $column): string => $column['name'] ?? '', $columns);
    $rows = [];
    if (in_array('upload_id', $columnNames, true)) {
        $rows = $legacy->query('SELECT code, upload_id, expires_at, created_at FROM shortcodes')->fetchAll();
    } elseif (in_array('target', $columnNames, true)) {
        $rows = $legacy->query('SELECT code, target, expires_at, created_at FROM shortcodes')->fetchAll();
    }

    if (!$rows) {
        return $pdo;
    }

    $insert = $pdo->prepare('INSERT OR IGNORE INTO shortcodes (code, upload_id, expires_at, created_at) VALUES (:code, :upload_id, :expires_at, :created_at)');
    foreach ($rows as $row) {
        $uploadId = $row['upload_id'] ?? null;
        if (!$uploadId && isset($row['target'])) {
            $target = (string)$row['target'];
            $parts = parse_url($target);
            if ($parts === false) {
                continue;
            }
            $query = $parts['query'] ?? '';
            parse_str($query, $params);
            $uploadId = ih_sanitize_id($params['id'] ?? null);
        }
        $uploadId = ih_sanitize_id($uploadId);
        if (!$uploadId) {
            continue;
        }
        $insert->execute([
            ':code' => $row['code'],
            ':upload_id' => $uploadId,
            ':expires_at' => $row['expires_at'],
            ':created_at' => $row['created_at'],
        ]);
    }

    return $pdo;
}

function short_generate_code(int $minLen = 6, int $maxLen = 8): string
{
    if ($minLen < 1 || $maxLen < $minLen) {
        throw new InvalidArgumentException('Invalid shortcode length range.');
    }
    $len = random_int($minLen, $maxLen);
    $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $maxIndex = strlen($alphabet) - 1;
    $code = '';
    for ($i = 0; $i < $len; $i++) {
        $code .= $alphabet[random_int(0, $maxIndex)];
    }
    return $code;
}

function short_is_valid_code(string $code): bool
{
    if ($code === '' || str_contains($code, "\n") || str_contains($code, "\r")) {
        return false;
    }
    return (bool)preg_match('/^[0-9A-Za-z]{6,8}$/', $code);
}

function short_create(string $uploadId, int $expires_at): string
{
    $uploadId = ih_sanitize_id($uploadId);
    if (!$uploadId) {
        throw new InvalidArgumentException('Invalid upload id for shortcode.');
    }

    $pdo = short_init();
    $stmt = $pdo->prepare('INSERT INTO shortcodes (code, upload_id, expires_at, created_at) VALUES (:code, :upload_id, :expires_at, :created_at)');
    $createdAt = time();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = short_generate_code();
        try {
            $stmt->execute([
                ':code' => $code,
                ':upload_id' => $uploadId,
                ':expires_at' => $expires_at,
                ':created_at' => $createdAt,
            ]);
            return $code;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                continue;
            }
            throw $exception;
        }
    }

    throw new RuntimeException('Unable to allocate shortcode.');
}

function short_resolve(string $code): ?array
{
    $pdo = short_init();
    $stmt = $pdo->prepare('SELECT upload_id, expires_at FROM shortcodes WHERE code = :code LIMIT 1');
    $stmt->execute([':code' => $code]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }
    return $row;
}

function short_purge_expired(): void
{
    $pdo = short_init();
    $stmt = $pdo->prepare('DELETE FROM shortcodes WHERE expires_at <= :now');
    $stmt->execute([':now' => time()]);
}

function short_delete_by_upload(string $uploadId): void
{
    $pdo = short_init();
    $stmt = $pdo->prepare('DELETE FROM shortcodes WHERE upload_id = :upload_id');
    $stmt->execute([':upload_id' => $uploadId]);
}
