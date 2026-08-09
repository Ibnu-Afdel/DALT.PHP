<?php

declare(strict_types=1);

const RESULT_MARKER = '__DALT_TEST_RESULT__';

ini_set('display_errors', '0');
ini_set('log_errors', '0');

$payload = json_decode(
    base64_decode($argv[1] ?? '', true),
    true,
    flags: JSON_THROW_ON_ERROR,
);

$_GET = $payload['query'] ?? [];
$_POST = $payload['input'] ?? [];
$_COOKIE = [];
$_FILES = [];
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'false';
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_SERVER = array_merge([
    'REQUEST_METHOD' => strtoupper($payload['method'] ?? 'GET'),
    'REQUEST_URI' => $payload['uri'] ?? '/',
    'HTTP_HOST' => 'localhost',
    'SERVER_NAME' => 'localhost',
    'SERVER_PORT' => '80',
    'SCRIPT_NAME' => '/index.php',
    'APP_ENV' => 'testing',
    'APP_DEBUG' => 'false',
    'DB_DRIVER' => 'sqlite',
    'DB_DATABASE' => ':memory:',
], $payload['server'] ?? []);

$sessionDirectory = sys_get_temp_dir() . '/dalt-http-test-' . bin2hex(random_bytes(8));

if (!mkdir($sessionDirectory, 0700) && !is_dir($sessionDirectory)) {
    throw new RuntimeException("Unable to create test session directory: {$sessionDirectory}");
}

$_ENV['APP_LOG_PATH'] = $sessionDirectory . '/app.log';
$_SERVER['APP_LOG_PATH'] = $_ENV['APP_LOG_PATH'];
session_save_path($sessionDirectory);
ob_start();

register_shutdown_function(static function () use ($sessionDirectory): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $body = (string) ob_get_contents();
    ob_end_clean();

    $status = http_response_code();
    $result = [
        'status' => is_int($status) ? $status : 200,
        'body' => $body,
        'headers' => headers_list(),
        'error' => error_get_last(),
    ];

    foreach (scandir($sessionDirectory) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $sessionDirectory . '/' . $entry;
        if (is_file($path)) {
            unlink($path);
        }
    }
    rmdir($sessionDirectory);

    echo RESULT_MARKER . base64_encode(json_encode($result, JSON_THROW_ON_ERROR));
});

$projectRoot = $payload['project_root'] ?? dirname(__DIR__, 2);

if (!is_string($projectRoot) || !is_file($projectRoot . '/public/index.php')) {
    throw new RuntimeException('Application test project root is invalid.');
}

require $projectRoot . '/public/index.php';
