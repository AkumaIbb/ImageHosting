<?php
declare(strict_types=1);

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
    return $data;
}

function ih_save_upload(array $upload): void
{
    $upload['updated_at'] = time();
    $path = ih_upload_path($upload['id']);
    file_put_contents($path, json_encode($upload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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

function ih_guess_extension(string $name, string $mime): string
{
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($extension && preg_match('/^[a-z0-9]+$/', $extension)) {
        return $extension;
    }
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/bmp' => 'bmp',
    ];
    return $map[$mime] ?? 'bin';
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
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName);
    if ($mime && str_starts_with($mime, 'image/')) {
        return $mime;
    }
    return null;
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
}

