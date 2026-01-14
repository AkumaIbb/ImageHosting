<?php
declare(strict_types=1);

function ih_admin_ids(): array
{
    static $cache = null;
    $now = time();
    $path = dirname(__DIR__) . '/config/admin_ids.txt';
    $mtime = is_file($path) ? (int)filemtime($path) : 0;
    if (is_array($cache)) {
        $cachedAt = $cache['loaded_at'] ?? 0;
        $cachedMtime = $cache['mtime'] ?? 0;
        if (($now - $cachedAt) < 60 && $cachedMtime === $mtime) {
            return $cache['data'] ?? [];
        }
    }

    if (!is_file($path)) {
        $cache = [
            'data' => [],
            'loaded_at' => $now,
            'mtime' => 0,
        ];
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        $cache = [
            'data' => [],
            'loaded_at' => $now,
            'mtime' => $mtime,
        ];
        return [];
    }

    $ids = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (preg_match('/^[0-9A-Za-z]{12,16}$/', $line)) {
            $ids[$line] = true;
        }
    }

    $cache = [
        'data' => array_keys($ids),
        'loaded_at' => $now,
        'mtime' => $mtime,
    ];
    return $cache['data'];
}

function is_admin(?string $userId): bool
{
    if (!$userId) {
        return false;
    }
    if (!in_array($userId, ih_admin_ids(), true)) {
        return false;
    }
    return ih_admin_cookie_valid($userId);
}

function ih_admin_config(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $path = dirname(__DIR__) . '/config/secret.php';
    if (!is_file($path)) {
        $cache = [];
        return $cache;
    }
    $data = require $path;
    if (!is_array($data)) {
        $cache = [];
        return $cache;
    }
    $cache = $data;
    return $cache;
}

function ih_admin_hmac_secret(): ?string
{
    $config = ih_admin_config();
    $secret = $config['admin_hmac_secret'] ?? null;
    if (!is_string($secret) || $secret === '') {
        return null;
    }
    return $secret;
}

function ih_admin_login_token(): ?string
{
    $config = ih_admin_config();
    $token = $config['admin_login_token'] ?? null;
    if (!is_string($token) || $token === '') {
        return null;
    }
    return $token;
}

function ih_admin_cookie_valid(string $userId): bool
{
    $secret = ih_admin_hmac_secret();
    if (!$secret) {
        return false;
    }
    $cookie = $_COOKIE['ih_admin'] ?? null;
    if (!is_string($cookie) || $cookie === '') {
        return false;
    }
    $decoded = base64_decode($cookie, true);
    if (!is_string($decoded)) {
        return false;
    }
    $parts = explode('|', $decoded);
    if (count($parts) !== 3) {
        return false;
    }
    [$cookieUserId, $timestamp, $hmac] = $parts;
    if ($cookieUserId !== $userId) {
        return false;
    }
    if (!preg_match('/^[0-9A-Za-z]{12,16}$/', $cookieUserId)) {
        return false;
    }
    if (!ctype_digit($timestamp)) {
        return false;
    }
    $issuedAt = (int)$timestamp;
    if ($issuedAt <= 0) {
        return false;
    }
    $maxAge = 60 * 60 * 24 * 7;
    if ((time() - $issuedAt) > $maxAge) {
        return false;
    }
    $expected = hash_hmac('sha256', $cookieUserId . '|' . $issuedAt, $secret);
    return hash_equals($expected, $hmac);
}

function ih_admin_cookie_value(string $userId, int $issuedAt): ?string
{
    $secret = ih_admin_hmac_secret();
    if (!$secret) {
        return null;
    }
    $payload = $userId . '|' . $issuedAt;
    $hmac = hash_hmac('sha256', $payload, $secret);
    return base64_encode($payload . '|' . $hmac);
}

function ih_admin_login(string $userId, string $token): bool
{
    if (!in_array($userId, ih_admin_ids(), true)) {
        return false;
    }
    $expectedToken = ih_admin_login_token();
    if (!$expectedToken || !hash_equals($expectedToken, $token)) {
        return false;
    }
    $issuedAt = time();
    $value = ih_admin_cookie_value($userId, $issuedAt);
    if (!$value) {
        return false;
    }
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('ih_admin', $value, [
        'expires' => $issuedAt + (60 * 60 * 24 * 7),
        'path' => '/',
        'samesite' => 'Lax',
        'httponly' => true,
        'secure' => $secure,
    ]);
    return true;
}
