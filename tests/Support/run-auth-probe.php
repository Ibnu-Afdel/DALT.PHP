<?php

declare(strict_types=1);

use Core\Authenticator;
use Core\Database;
use Core\Session;

const BASE_PATH = __DIR__ . '/../../';

require BASE_PATH . 'vendor/autoload.php';
require BASE_PATH . 'framework/Core/functions.php';

final class ProbeAuthenticationDatabase extends Database
{
    /** @param array<string, mixed>|false $user */
    public function __construct(private readonly array|false $user)
    {
    }

    /** @param array<string, mixed> $params */
    public function query($query, $params = []): static
    {
        return $this;
    }

    /** @return array<string, mixed>|false */
    public function find(): array|false
    {
        return $this->user;
    }
}

$payload = json_decode(base64_decode($argv[1] ?? '', true), true, flags: JSON_THROW_ON_ERROR);
$sessionDirectory = sys_get_temp_dir() . '/dalt-auth-probe-' . bin2hex(random_bytes(8));
mkdir($sessionDirectory, 0700, true);
session_save_path($sessionDirectory);

Session::start([
    'driver' => 'file',
    'name' => 'dalt_auth_probe',
    'lifetime' => 120,
    'cookie' => [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
]);

$beforeAttempt = session_id();

try {
    $authenticated = (new Authenticator(new ProbeAuthenticationDatabase($payload['user'])))
        ->attempt($payload['email'], $payload['password']);
    $result = [
        'authenticated' => $authenticated,
        'id_changed' => $beforeAttempt !== session_id(),
        'identity' => $_SESSION['user'] ?? null,
    ];
} catch (Throwable $exception) {
    $result = [
        'exception' => $exception::class,
        'message' => $exception->getMessage(),
    ];
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

foreach (scandir($sessionDirectory) ?: [] as $entry) {
    if ($entry !== '.' && $entry !== '..') {
        unlink($sessionDirectory . '/' . $entry);
    }
}

rmdir($sessionDirectory);

echo json_encode($result, JSON_THROW_ON_ERROR);
