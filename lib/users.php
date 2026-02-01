<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/settings.php';

function ih_get_user_id_cookie(): ?string
{
    $cookie = $_COOKIE['ih_uid'] ?? null;
    if (!is_string($cookie) || $cookie === '') {
        return null;
    }
    if (!preg_match('/^[0-9A-Za-z]{12,16}$/', $cookie)) {
        return null;
    }
    return $cookie;
}

function ih_generate_user_id(int $length = 14): string
{
    if ($length < 12 || $length > 16) {
        $length = 14;
    }
    $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $maxIndex = strlen($alphabet) - 1;
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $alphabet[random_int(0, $maxIndex)];
    }
    return $id;
}

function ih_create_user(): string
{
    $pdo = ih_db();
    $stmt = $pdo->prepare('INSERT INTO users (user_id, created_at, ttl_seconds, is_banned) VALUES (:user_id, :created_at, :ttl_seconds, 0)');
    $defaultTtl = ih_get_default_ttl_seconds();
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $userId = ih_generate_user_id();
        try {
            $stmt->execute([
                ':user_id' => $userId,
                ':created_at' => time(),
                ':ttl_seconds' => $defaultTtl,
            ]);
            return $userId;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                continue;
            }
            throw $exception;
        }
    }

    throw new RuntimeException('Unable to allocate user id.');
}

function ih_get_user(string $userId): ?array
{
    $pdo = ih_db();
    $stmt = $pdo->prepare('SELECT user_id, created_at, ttl_seconds, is_banned FROM users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $userId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function ih_allowed_ttl_options(bool $isAdmin): array
{
    $options = [
        '1h' => 3600,
        '2h' => 7200,
        '6h' => 21600,
        '12h' => 43200,
        '24h' => 86400,
        '2d' => 172800,
        '3d' => 259200,
        '4d' => 345600,
        '5d' => 432000,
        '6d' => 518400,
        '7d' => 604800,
    ];

    if ($isAdmin) {
        $options += [
            '14d' => 1209600,
            '30d' => 2592000,
            '60d' => 5184000,
            '90d' => 7776000,
            '120d' => 10368000,
            '180d' => 15552000,
            'unlimited' => IH_UNLIMITED_TTL,
        ];
    } elseif (ih_get_default_ttl_seconds() === null) {
        $options['unlimited'] = IH_UNLIMITED_TTL;
    }

    return $options;
}

function ih_normalize_ttl_selection($value, bool $isAdmin): ?int
{
    $options = ih_allowed_ttl_options($isAdmin);

    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
        $intValue = (int)$value;
        foreach ($options as $seconds) {
            if ($seconds !== null && $seconds === $intValue) {
                return $intValue;
            }
        }
        return null;
    }

    if (is_string($value)) {
        $value = trim($value);
        if (array_key_exists($value, $options)) {
            return $options[$value];
        }
    }

    return null;
}

function ih_save_user_ttl(string $userId, ?int $ttlSeconds): void
{
    $pdo = ih_db();
    $stmt = $pdo->prepare('UPDATE users SET ttl_seconds = :ttl_seconds WHERE user_id = :user_id');
    $stmt->execute([
        ':ttl_seconds' => $ttlSeconds,
        ':user_id' => $userId,
    ]);
}

function ih_effective_ttl_seconds(?int $storedTtl, bool $isAdmin): ?int
{
    $options = ih_allowed_ttl_options($isAdmin);
    if ($storedTtl === null) {
        return ih_get_default_ttl_seconds();
    }

    if (in_array($storedTtl, array_filter($options, static fn($value): bool => $value !== null), true)) {
        return $storedTtl;
    }

    return ih_get_default_ttl_seconds();
}

function ih_ttl_options_payload(bool $isAdmin): array
{
    $options = ih_allowed_ttl_options($isAdmin);
    $payload = [];
    foreach ($options as $label => $seconds) {
        $payload[] = [
            'label' => $label,
            'seconds' => $seconds,
        ];
    }
    return $payload;
}
