<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/logger.php';

if (!isset($GLOBALS['request_id'])) {
    $GLOBALS['request_id'] = uniqid('req_', true);
}

function api_request_id(): string
{
    return $GLOBALS['request_id'] ?? 'req_unknown';
}

ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline): bool {
    $level = in_array($errno, [E_WARNING, E_USER_WARNING, E_NOTICE, E_USER_NOTICE], true) ? 'warning' : 'error';
    log_msg($level, 'PHP error', [
        'errno' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
    ]);

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'internal_error',
        'request_id' => api_request_id(),
    ]);
    exit;
});

set_exception_handler(static function (Throwable $exception): void {
    log_msg('error', 'Unhandled exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'internal_error',
        'request_id' => api_request_id(),
    ]);
    exit;
});
