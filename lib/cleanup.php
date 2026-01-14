<?php
declare(strict_types=1);

require_once __DIR__ . '/uploads.php';

function ih_maybe_cleanup(int $ttlSeconds = 172800): void
{
    ih_ensure_dirs();
    $lockPath = ih_base_dir() . '/data/.cleanup.lock';
    $now = time();
    $lastRun = is_file($lockPath) ? (int)file_get_contents($lockPath) : 0;
    if ($now - $lastRun < 300) {
        return;
    }
    file_put_contents($lockPath, (string)$now);

    $dataDir = ih_data_dir();
    if (!is_dir($dataDir)) {
        return;
    }

    foreach (glob($dataDir . '/*.json') as $file) {
        $payload = json_decode((string)file_get_contents($file), true);
        if (!is_array($payload) || empty($payload['created_at'])) {
            continue;
        }
        if ($payload['created_at'] + $ttlSeconds < $now) {
            ih_delete_upload($payload);
        }
    }
}

