<?php
declare(strict_types=1);

function ih_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $storageDir = dirname(__DIR__) . '/storage';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0777, true);
    }

    $dbPath = $storageDir . '/app.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA busy_timeout=2000');
    $pdo->exec('PRAGMA journal_mode=WAL');

    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        user_id TEXT PRIMARY KEY,
        created_at INTEGER NOT NULL,
        ttl_seconds INTEGER NULL,
        is_banned INTEGER NOT NULL DEFAULT 0
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS uploads (
        upload_id TEXT PRIMARY KEY,
        user_id TEXT NULL,
        created_at INTEGER NOT NULL,
        expires_at INTEGER NULL,
        type TEXT NOT NULL,
        short_code TEXT NULL,
        preview_file TEXT NULL,
        file_count INTEGER NOT NULL DEFAULT 0
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS shortcodes (
        code TEXT PRIMARY KEY,
        upload_id TEXT NOT NULL,
        expires_at INTEGER NOT NULL,
        created_at INTEGER NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT NULL
    )');

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_uploads_user ON uploads(user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_uploads_expires ON uploads(expires_at)');

    return $pdo;
}
