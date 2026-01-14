<?php
declare(strict_types=1);

function base_url(): string
{
    $isHttps = false;
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        $isHttps = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $forwardedProto = strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if ($forwardedProto === 'https') {
            $isHttps = true;
        }
    }

    $hostHeader = $_SERVER['HTTP_X_FORWARDED_HOST']
        ?? $_SERVER['HTTP_HOST']
        ?? $_SERVER['SERVER_NAME']
        ?? 'localhost';
    $hostHeader = trim(explode(',', (string)$hostHeader)[0]);

    $host = $hostHeader;
    $port = null;
    if (str_starts_with($hostHeader, '[')) {
        $end = strpos($hostHeader, ']');
        if ($end !== false) {
            $host = substr($hostHeader, 0, $end + 1);
            $portPart = substr($hostHeader, $end + 1);
            if (str_starts_with($portPart, ':')) {
                $port = substr($portPart, 1);
            }
        }
    } elseif (str_contains($hostHeader, ':')) {
        [$host, $port] = explode(':', $hostHeader, 2);
    }

    if ($port === null && !empty($_SERVER['SERVER_PORT'])) {
        $port = (string)$_SERVER['SERVER_PORT'];
    }

    $scheme = $isHttps ? 'https' : 'http';
    $portSuffix = '';
    if ($port !== null && $port !== '') {
        $portValue = (int)$port;
        if (!($scheme === 'http' && $portValue === 80) && !($scheme === 'https' && $portValue === 443)) {
            $portSuffix = ':' . $portValue;
        }
    }

    return $scheme . '://' . $host . $portSuffix;
}
