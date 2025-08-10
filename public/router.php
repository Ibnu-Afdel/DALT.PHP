<?php
// Built-in server router
// If the requested path is an existing file, let the server handle it
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
// Otherwise, use the front controller
require __DIR__ . '/index.php'; 