<?php

declare(strict_types=1);

// Router for PHP's built-in server in local development:
//   php -S 127.0.0.1:8000 -t public tools/dev-server.php
// Serves existing files from public/ directly and sends everything else to Laravel.

$path = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if ($path !== '/' && is_file(__DIR__.'/../public'.$path)) {
    return false;
}

require __DIR__.'/../public/index.php';
