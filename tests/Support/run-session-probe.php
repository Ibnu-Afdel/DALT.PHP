<?php

declare(strict_types=1);

use Core\Authenticator;
use Core\Session;

const BASE_PATH = __DIR__ . '/../../';

require BASE_PATH . 'vendor/autoload.php';
require BASE_PATH . 'framework/Core/functions.php';

$sessionDirectory = sys_get_temp_dir() . '/dalt-session-probe-' . bin2hex(random_bytes(8));
mkdir($sessionDirectory, 0700, true);
session_save_path($sessionDirectory);
$_SERVER['HTTPS'] = 'on';

$defaultConfig = [
    'driver' => 'file',
    'name' => 'dalt_session_probe',
    'lifetime' => 120,
    'cookie' => [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => null,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];

$encodedConfig = $argv[1] ?? '';
$config = $encodedConfig === ''
    ? $defaultConfig
    : json_decode(base64_decode($encodedConfig, true), true, flags: JSON_THROW_ON_ERROR);

$result = [];

try {
    Session::start($config);
    $cookie = session_get_cookie_params();
    $beforeLogin = session_id();
    (new Authenticator())->login(['id' => 42, 'email' => 'learner@example.com']);
    $afterLogin = session_id();
    $_COOKIE[session_name()] = $afterLogin;

    try {
        Session::start($config);
    } catch (Throwable $exception) {
        $alreadyActive = $exception->getMessage();
    }

    $result = [
        'started' => true,
        'status' => session_status(),
        'name' => session_name(),
        'cookie' => $cookie,
        'strict' => ini_get('session.use_strict_mode'),
        'use_cookies' => ini_get('session.use_cookies'),
        'cookies_only' => ini_get('session.use_only_cookies'),
        'trans_sid' => ini_get('session.use_trans_sid'),
        'save_handler' => ini_get('session.save_handler'),
        'gc_lifetime' => ini_get('session.gc_maxlifetime'),
        'id_changed' => $beforeLogin !== $afterLogin,
        'user' => $_SESSION['user'] ?? null,
        'already_active' => $alreadyActive ?? null,
    ];

    Session::destroy();
    $result['destroyed_status'] = session_status();
    $result['destroyed_data'] = $_SESSION;
    $result['cookie_removed'] = !array_key_exists('dalt_session_probe', $_COOKIE);
} catch (Throwable $exception) {
    $result = [
        'started' => false,
        'exception' => $exception::class,
        'message' => $exception->getMessage(),
    ];
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

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

echo json_encode($result, JSON_THROW_ON_ERROR);
