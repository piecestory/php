<?php

declare(strict_types=1);

// Router for PHP's built-in server in local development:
//   php -S 127.0.0.1:8000 -t public tools/dev-server.php
// Serves existing files from public/ directly and sends everything else to Laravel.

$path = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if ($path !== '/' && is_file(__DIR__.'/../public'.$path)) {
    return false;
}

// Uploaded media (/storage/...) straight from storage/app/public. On Windows the public/storage
// junction is not always traversable (long paths); production uses a normal symlink instead.
if (str_starts_with($path, '/storage/')) {
    $root = realpath(__DIR__.'/../storage/app/public');
    $file = realpath($root.substr($path, strlen('/storage')));

    if ($root !== false && $file !== false && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file)) {
        header('Content-Type: '.(mime_content_type($file) ?: 'application/octet-stream'));
        header('Content-Length: '.filesize($file));
        readfile($file);

        return true;
    }
}

require __DIR__.'/../public/index.php';
