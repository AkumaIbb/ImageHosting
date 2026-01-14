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
        target TEXT NOT NULL,
        expires_at INTEGER NOT NULL,
        created_at INTEGER NOT NULL
    )');

    return $pdo;
}

function short_generate_code(int $len = 8): string
{
    $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $maxIndex = strlen($alphabet) - 1;
    $code = '';
    for ($i = 0; $i < $len; $i++) {
        $code .= $alphabet[random_int(0, $maxIndex)];
    }
    return $code;
}

function short_is_safe_target(string $target): bool
{
    if ($target === '' || str_contains($target, "\n") || str_contains($target, "\r")) {
        return false;
    }
    $parts = parse_url($target);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
        return false;
    }
    if (str_starts_with($target, '/')) {
        return true;
    }
    return str_starts_with($target, 'v.php');
}

function short_create(string $target, int $expires_at): string
{
    if (!short_is_safe_target($target)) {
        throw new InvalidArgumentException('Invalid target for shortcode.');
    }

    $pdo = short_init();
    $stmt = $pdo->prepare('INSERT INTO shortcodes (code, target, expires_at, created_at) VALUES (:code, :target, :expires_at, :created_at)');
    $createdAt = time();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = short_generate_code(8);
        try {
            $stmt->execute([
                ':code' => $code,
                ':target' => $target,
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
    $stmt = $pdo->prepare('SELECT target, expires_at FROM shortcodes WHERE code = :code LIMIT 1');
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
