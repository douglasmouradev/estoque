<?php

declare(strict_types=1);

/** Router para: php -S localhost:8080 router.php (a partir da pasta public) */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file) && !is_dir($file)) {
    return false;
}

require __DIR__ . '/index.php';
