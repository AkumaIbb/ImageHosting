<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/db.php';

function ih_get_setting(string $key): ?string
{
    $pdo = ih_db();
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = :key LIMIT 1');
    $stmt->execute([':key' => $key]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }
    return is_string($row['value'] ?? null) ? $row['value'] : null;
}

function ih_set_setting(string $key, string $value): void
{
    $pdo = ih_db();
    $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (:key, :value)
        ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $stmt->execute([
        ':key' => $key,
        ':value' => $value,
    ]);
}

function ih_get_default_ttl_seconds(): ?int
{
    $raw = ih_get_setting('default_ttl_seconds');
    if ($raw === null) {
        return IH_DEFAULT_TTL_SECONDS;
    }
    if ($raw === 'unlimited') {
        return null;
    }
    if (ctype_digit($raw)) {
        $seconds = (int)$raw;
        if ($seconds <= 0) {
            return null;
        }
        return $seconds;
    }
    return IH_DEFAULT_TTL_SECONDS;
}

function ih_set_default_ttl_seconds(?int $seconds): void
{
    if ($seconds === null) {
        ih_set_setting('default_ttl_seconds', 'unlimited');
        return;
    }
    ih_set_setting('default_ttl_seconds', (string)$seconds);
}
