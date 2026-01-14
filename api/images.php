<?php
declare(strict_types=1);

header('Content-Type: application/json');

http_response_code(410);
echo json_encode([
    'ok' => false,
    'error' => 'Dieser Endpunkt ist deaktiviert.',
]);
