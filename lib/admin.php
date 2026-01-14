<?php
declare(strict_types=1);

function ih_admin_ids(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $path = dirname(__DIR__) . '/config/admin_ids.txt';
    if (!is_file($path)) {
        $cache = [];
        return $cache;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        $cache = [];
        return $cache;
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

    $cache = array_keys($ids);
    return $cache;
}

function is_admin(?string $userId): bool
{
    if (!$userId) {
        return false;
    }
    return in_array($userId, ih_admin_ids(), true);
}
