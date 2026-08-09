<?php

declare(strict_types=1);

/** @param array<string, mixed>|null $config */
function runNativeSessionProbe(?array $config = null): array
{
    $argument = $config === null
        ? ''
        : base64_encode(json_encode($config, JSON_THROW_ON_ERROR));

    $process = proc_open(
        [PHP_BINARY, base_path('tests/Support/run-session-probe.php'), $argument],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        BASE_PATH,
        null,
        ['bypass_shell' => true],
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start native session probe.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0 || $stderr !== '') {
        throw new RuntimeException(
            "Native session probe failed with exit code {$exitCode}. STDERR: {$stderr}",
        );
    }

    return json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
}

test('native startup applies security cookie lifetime regeneration and destruction contracts', function () {
    $result = runNativeSessionProbe();

    expect($result['started'])->toBeTrue()
        ->and($result['status'])->toBe(PHP_SESSION_ACTIVE)
        ->and($result['name'])->toBe('dalt_session_probe')
        ->and($result['cookie']['lifetime'])->toBe(0)
        ->and($result['cookie']['path'])->toBe('/')
        ->and($result['cookie']['secure'])->toBeTrue()
        ->and($result['cookie']['httponly'])->toBeTrue()
        ->and($result['cookie']['samesite'])->toBe('Lax')
        ->and($result['strict'])->toBe('1')
        ->and($result['use_cookies'])->toBe('1')
        ->and($result['cookies_only'])->toBe('1')
        ->and($result['trans_sid'])->toBe('0')
        ->and($result['save_handler'])->toBe('files')
        ->and($result['gc_lifetime'])->toBe('7200')
        ->and($result['id_changed'])->toBeTrue()
        ->and($result['user'])->toBe(['email' => 'learner@example.com'])
        ->and($result['already_active'])->toBe('The session has already been started.')
        ->and($result['destroyed_status'])->toBe(PHP_SESSION_NONE)
        ->and($result['destroyed_data'])->toBe([])
        ->and($result['cookie_removed'])->toBeTrue();
});

test('native startup rejects invalid configuration before opening a session', function (
    array $config,
    string $message,
) {
    $result = runNativeSessionProbe($config);

    expect($result['started'])->toBeFalse()
        ->and($result['message'])->toContain($message);
})->with([
    'driver' => [[
        'driver' => 'database',
        'name' => 'probe',
        'lifetime' => 120,
        'cookie' => [],
    ], 'only the native file session driver'],
    'name' => [[
        'driver' => 'file',
        'name' => '',
        'lifetime' => 120,
        'cookie' => [],
    ], 'name must be a non-empty string'],
    'lifetime' => [[
        'driver' => 'file',
        'name' => 'probe',
        'lifetime' => 0,
        'cookie' => [],
    ], 'lifetime must be a positive integer'],
    'cookie' => [[
        'driver' => 'file',
        'name' => 'probe',
        'lifetime' => 120,
        'cookie' => 'invalid',
    ], 'cookie configuration must be an array'],
    'same site' => [[
        'driver' => 'file',
        'name' => 'probe',
        'lifetime' => 120,
        'cookie' => [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => null,
            'httponly' => true,
            'samesite' => 'None',
        ],
    ], 'samesite must be Lax or Strict'],
]);
