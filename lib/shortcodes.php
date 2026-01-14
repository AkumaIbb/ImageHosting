<?php
declare(strict_types=1);

require_once __DIR__ . '/uploads.php';

function short_init(): PDO
{
    $storageDir = ih_base_dir() . '/storage';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0777, true);
    }

    $dbPath = $storageDir . '/shortcodes.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA busy_timeout=2000');
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('CREATE TABLE IF NOT EXISTS shortcodes (
        code TEXT PRIMARY KEY,
        upload_id TEXT NOT NULL,
        expires_at INTEGER NOT NULL,
        created_at INTEGER NOT NULL
    )');

    $columns = $pdo->query('PRAGMA table_info(shortcodes)')->fetchAll();
    $columnNames = array_map(static fn(array $column): string => $column['name'] ?? '', $columns);
    if (in_array('target', $columnNames, true) && !in_array('upload_id', $columnNames, true)) {
        $pdo->exec('ALTER TABLE shortcodes RENAME TO shortcodes_legacy');
        $pdo->exec('CREATE TABLE shortcodes (
            code TEXT PRIMARY KEY,
            upload_id TEXT NOT NULL,
            expires_at INTEGER NOT NULL,
            created_at INTEGER NOT NULL
        )');

        $rows = $pdo->query('SELECT code, target, expires_at, created_at FROM shortcodes_legacy')->fetchAll();
        $insert = $pdo->prepare('INSERT INTO shortcodes (code, upload_id, expires_at, created_at) VALUES (:code, :upload_id, :expires_at, :created_at)');
        foreach ($rows as $row) {
            $target = (string)($row['target'] ?? '');
            $parts = parse_url($target);
            if ($parts === false) {
                continue;
            }
            $query = $parts['query'] ?? '';
            parse_str($query, $params);
            $uploadId = ih_sanitize_id($params['id'] ?? null);
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

        $pdo->exec('DROP TABLE shortcodes_legacy');
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
