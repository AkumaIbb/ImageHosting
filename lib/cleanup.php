<?php
declare(strict_types=1);

require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/shortcodes.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';

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

    $pdo = ih_db();
    $stmt = $pdo->prepare('SELECT upload_id FROM uploads WHERE expires_at IS NOT NULL AND expires_at <= :now');
    try {
        $stmt->execute([':now' => $now]);
        $expired = $stmt->fetchAll();
    } catch (Throwable $exception) {
        log_msg('error', 'cleanup query failed', ['error' => $exception->getMessage()]);
        return;
    }

    foreach ($expired as $row) {
        $uploadId = ih_sanitize_id($row['upload_id'] ?? null);
        if (!$uploadId) {
            continue;
        }
        $upload = ih_load_upload($uploadId);
        if ($upload) {
            ih_delete_upload($upload);
            short_delete_by_upload($uploadId);
        } else {
            $pdo->prepare('DELETE FROM uploads WHERE upload_id = :upload_id')->execute([':upload_id' => $uploadId]);
            short_delete_by_upload($uploadId);
        }
    }

    try {
        short_purge_expired();
    } catch (Throwable $exception) {
        log_msg('warning', 'shortcode purge failed', ['error' => $exception->getMessage()]);
    }
}
