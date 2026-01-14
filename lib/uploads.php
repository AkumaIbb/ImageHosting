<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function ih_base_dir(): string
{
    return dirname(__DIR__);
}

function ih_data_dir(): string
{
    return ih_base_dir() . '/data/uploads';
}

function ih_storage_dir(): string
{
    return ih_base_dir() . '/public/storage';
}

function ih_allowed_mime_types(): array
{
    return [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
}

function ih_ensure_dirs(): void
{
    foreach ([ih_data_dir(), ih_storage_dir()] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

function ih_sanitize_id(?string $id): ?string
{
    if (!$id) {
        return null;
    }
    if (!preg_match('/^[a-zA-Z0-9_-]{6,64}$/', $id)) {
        return null;
    }
    return $id;
}

function ih_generate_id(): string
{
    return bin2hex(random_bytes(8));
}

function ih_upload_path(string $uploadId): string
{
    return ih_data_dir() . '/' . $uploadId . '.json';
}

function ih_load_upload(string $uploadId): ?array
{
    $path = ih_upload_path($uploadId);
    if (!is_file($path)) {
        return null;
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        return null;
    }
    $data = json_decode($contents, true);
    if (!is_array($data)) {
        return null;
    }
    if (!array_key_exists('expires_at', $data) && isset($data['created_at'])) {
        $data['expires_at'] = (int)$data['created_at'] + 172800;
    }
    if (array_key_exists('expires_at', $data) && $data['expires_at'] !== null) {
        $expiresAt = (int)$data['expires_at'];
        if ($expiresAt < time()) {
            return null;
        }
        $data['expires_at'] = $expiresAt;
    }
    return $data;
}

function ih_save_upload(array $upload): void
{
    $upload['updated_at'] = time();
    $path = ih_upload_path($upload['id']);
    file_put_contents($path, json_encode($upload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    ih_sync_upload_record($upload);
}

function ih_sync_upload_record(array $upload): void
{
    $uploadId = $upload['id'] ?? null;
    if (!is_string($uploadId) || $uploadId === '') {
        return;
    }
    $previewFile = null;
    $fileCount = 0;
    if (!empty($upload['files']) && is_array($upload['files'])) {
        $fileCount = count($upload['files']);
        $first = $upload['files'][0] ?? null;
        if (is_array($first) && !empty($first['filename'])) {
            $previewFile = $first['filename'];
        }
    }

    $pdo = ih_db();
    $stmt = $pdo->prepare('INSERT INTO uploads (upload_id, user_id, created_at, expires_at, type, short_code, preview_file, file_count)
        VALUES (:upload_id, :user_id, :created_at, :expires_at, :type, :short_code, :preview_file, :file_count)
        ON CONFLICT(upload_id) DO UPDATE SET
            user_id = excluded.user_id,
            created_at = excluded.created_at,
            expires_at = excluded.expires_at,
            type = excluded.type,
            short_code = excluded.short_code,
            preview_file = excluded.preview_file,
            file_count = excluded.file_count');
    $stmt->execute([
        ':upload_id' => $uploadId,
        ':user_id' => $upload['user_id'] ?? null,
        ':created_at' => $upload['created_at'] ?? time(),
        ':expires_at' => $upload['expires_at'] ?? null,
        ':type' => $upload['type'] ?? 'single',
        ':short_code' => $upload['short_code'] ?? null,
        ':preview_file' => $previewFile,
        ':file_count' => $fileCount,
    ]);
}

function ih_collect_files(array $files): array
{
    $collected = [];
    $keys = ['files', 'file', 'image'];
    foreach ($keys as $key) {
        if (!isset($files[$key])) {
            continue;
        }
        $entry = $files[$key];
        if (is_array($entry['name'])) {
            foreach ($entry['name'] as $index => $name) {
                $collected[] = [
                    'name' => $name,
                    'type' => $entry['type'][$index] ?? '',
                    'tmp_name' => $entry['tmp_name'][$index] ?? '',
                    'error' => $entry['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $entry['size'][$index] ?? 0,
                ];
            }
        } else {
            $collected[] = $entry;
        }
    }
    return $collected;
}

function ih_extension_for_mime(string $mime): ?string
{
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    return $map[$mime] ?? null;
}

function ih_public_file_url(string $uploadId, string $filename): string
{
    return '/storage/' . rawurlencode($uploadId) . '/' . rawurlencode($filename);
}

function ih_is_image_file(string $tmpName): ?string
{
    if (!is_file($tmpName)) {
        return null;
    }
    $mime = ih_detect_mime_type($tmpName);
    if (!is_string($mime) || $mime === '') {
        return null;
    }
    if (!in_array($mime, ih_allowed_mime_types(), true)) {
        return null;
    }
    if (!ih_magic_bytes_match($tmpName, $mime)) {
        return null;
    }
    return $mime;
}

function ih_detect_mime_type(string $tmpName): ?string
{
    if (!is_file($tmpName)) {
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName);
    return is_string($mime) && $mime !== '' ? $mime : null;
}

function ih_magic_bytes_match(string $tmpName, string $mime): bool
{
    $handle = fopen($tmpName, 'rb');
    if ($handle === false) {
        return false;
    }
    $bytes = fread($handle, 12);
    fclose($handle);
    if ($bytes === false) {
        return false;
    }

    return match ($mime) {
        'image/jpeg' => strlen($bytes) >= 3 && substr($bytes, 0, 3) === "\xFF\xD8\xFF",
        'image/png' => strlen($bytes) >= 8 && substr($bytes, 0, 8) === "\x89PNG\r\n\x1A\n",
        'image/gif' => strlen($bytes) >= 6 && (substr($bytes, 0, 6) === 'GIF87a' || substr($bytes, 0, 6) === 'GIF89a'),
        'image/webp' => strlen($bytes) >= 12 && substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP',
        default => false,
    };
}

function ih_rate_limit_allow(string $key, int $limit, int $windowSeconds): bool
{
    $baseDir = dirname(__DIR__) . '/storage/ratelimit';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0777, true);
    }

    $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    $path = $baseDir . '/' . $safeKey . '.json';
    $now = time();

    $state = [
        'reset_at' => $now + $windowSeconds,
        'remaining' => $limit,
    ];

    if (is_file($path)) {
        $contents = file_get_contents($path);
        $decoded = $contents ? json_decode($contents, true) : null;
        if (is_array($decoded) && isset($decoded['reset_at'], $decoded['remaining'])) {
            $state = $decoded;
        }
    }

    if (!isset($state['reset_at'], $state['remaining']) || $state['reset_at'] <= $now) {
        $state = [
            'reset_at' => $now + $windowSeconds,
            'remaining' => $limit,
        ];
    }

    if ((int)$state['remaining'] <= 0) {
        return false;
    }

    $state['remaining'] = (int)$state['remaining'] - 1;
    file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return true;
}

function ih_delete_upload(array $upload): void
{
    $uploadDir = ih_storage_dir() . '/' . $upload['id'];
    if (is_dir($uploadDir)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($uploadDir);
    }
    $dataPath = ih_upload_path($upload['id']);
    if (is_file($dataPath)) {
        unlink($dataPath);
    }

    $pdo = ih_db();
    $stmt = $pdo->prepare('DELETE FROM uploads WHERE upload_id = :upload_id');
    $stmt->execute([':upload_id' => $upload['id'] ?? '']);
}
