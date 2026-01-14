<?php
declare(strict_types=1);

function log_msg(string $level, string $message, array $context = []): void
{
    $baseDir = dirname(__DIR__);
    $logDir = $baseDir . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $timestamp = date('c');
    $requestId = $GLOBALS['request_id'] ?? '-';
    $contextJson = $context ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '{}';
    $line = sprintf("%s [%s] %s %s %s\n", $timestamp, strtoupper($level), $requestId, $message, $contextJson);

    file_put_contents($logDir . '/app.log', $line, FILE_APPEND);
}
