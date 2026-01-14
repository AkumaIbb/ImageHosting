<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/api_bootstrap.php';

http_response_code(410);
echo json_encode([
    'ok' => false,
    'error' => 'Dieser Endpunkt ist deaktiviert.',
    'request_id' => api_request_id(),
]);
