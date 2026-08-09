<?php

declare(strict_types=1);

use Core\Authenticator;
use Core\Database;
use Core\Request;
use Core\Session;

final class RecordingAuthenticationDatabase extends Database
{
    /** @var list<array{query: string, params: array<string, mixed>}> */
    public array $queries = [];

    /** @param array<string, mixed>|false $user */
    public function __construct(private readonly array|false $user)
    {
    }

    /** @param array<string, mixed> $params */
    public function query($query, $params = []): static
    {
        $this->queries[] = ['query' => $query, 'params' => $params];

        return $this;
    }

    /** @return array<string, mixed>|false */
    public function find(): array|false
    {
        return $this->user;
    }
}

/** @param array<string, mixed>|false $user */
function runAuthenticationProbe(array|false $user, mixed $email, mixed $password): array
{
    $payload = base64_encode(json_encode([
        'user' => $user,
        'email' => $email,
        'password' => $password,
    ], JSON_THROW_ON_ERROR));

    $process = proc_open(
        [PHP_BINARY, base_path('tests/Support/run-auth-probe.php'), $payload],
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
        throw new RuntimeException('Unable to start authentication probe.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0 || $stderr !== '') {
        throw new RuntimeException("Authentication probe failed with exit code {$exitCode}: {$stderr}");
    }

    return json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
}

test('valid credentials authenticate and establish a canonical rotated identity', function () {
    $hash = password_hash('correct horse', PASSWORD_DEFAULT);
    $result = runAuthenticationProbe([
        'id' => 42,
        'name' => 'Dalt Learner',
        'email' => 'learner@example.com',
        'password' => $hash,
    ], 'learner@example.com', 'correct horse');

    expect($result)->toBe([
        'authenticated' => true,
        'id_changed' => true,
        'identity' => [
            'id' => 42,
            'email' => 'learner@example.com',
        ],
    ]);
});

test('unknown users and wrong passwords fail without authenticating', function (array|false $user) {
    $result = runAuthenticationProbe($user, 'learner@example.com', 'wrong password');

    expect($result)->toBe([
        'authenticated' => false,
        'id_changed' => false,
        'identity' => null,
    ]);
})->with([
    'unknown user' => false,
    'wrong password' => [[
        'id' => 42,
        'email' => 'learner@example.com',
        'password' => password_hash('correct password', PASSWORD_DEFAULT),
    ]],
]);

test('credential lookup binds the email and does not place it in SQL', function () {
    $database = new RecordingAuthenticationDatabase(false);
    $auth = new Authenticator($database);

    expect($auth->attempt('quoted\'@example.com', 'password'))->toBeFalse()
        ->and($database->queries)->toBe([[
            'query' => 'SELECT id, email, password FROM users WHERE email = :email',
            'params' => ['email' => 'quoted\'@example.com'],
        ]]);
});

test('malformed credential rows fail closed instead of throwing', function (array $user) {
    $result = runAuthenticationProbe($user, 'learner@example.com', 'password');

    expect($result)->toBe([
        'authenticated' => false,
        'id_changed' => false,
        'identity' => null,
    ]);
})->with([
    'missing password hash' => [[
        'id' => 42,
        'email' => 'learner@example.com',
    ]],
    'non-string password hash' => [[
        'id' => 42,
        'email' => 'learner@example.com',
        'password' => ['not-a-hash'],
    ]],
    'missing identity' => [[
        'email' => 'learner@example.com',
        'password' => password_hash('password', PASSWORD_DEFAULT),
    ]],
]);

test('authentication state accepts only the canonical session identity', function () {
    $auth = new Authenticator();

    expect($auth->check())->toBeFalse()
        ->and($auth->guest())->toBeTrue()
        ->and($auth->user())->toBeNull()
        ->and($auth->id())->toBeNull();

    $_SESSION['user'] = 'truthy but forged';
    expect($auth->check())->toBeFalse();

    $_SESSION['user'] = ['id' => 7, 'email' => 'learner@example.com'];
    expect($auth->check())->toBeTrue()
        ->and($auth->guest())->toBeFalse()
        ->and($auth->user())->toBe(['id' => 7, 'email' => 'learner@example.com'])
        ->and($auth->id())->toBe(7);
});

test('database string IDs are normalized to the canonical integer identity', function () {
    $_SESSION['user'] = ['id' => '7', 'email' => 'learner@example.com'];

    expect((new Authenticator())->user())->toBe([
        'id' => 7,
        'email' => 'learner@example.com',
    ]);
});

test('login rejects incomplete identities before mutating session state', function () {
    $_SESSION['existing'] = 'preserved';

    (new Authenticator())->login(['email' => 'learner@example.com']);
})->throws(InvalidArgumentException::class, 'requires a positive integer ID');

test('safe get requests can be remembered and consumed as an intended destination', function () {
    $auth = new Authenticator();
    $request = new Request(
        query: ['tab' => 'security'],
        server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/account?tab=security'],
    );

    $auth->rememberIntended($request);

    expect($auth->intended('/fallback'))->toBe('/account?tab=security')
        ->and($auth->intended('/fallback'))->toBe('/fallback');
});

test('unsafe or non-idempotent intended destinations are ignored', function (Request $request) {
    $auth = new Authenticator();
    $auth->rememberIntended($request);

    expect($auth->intended('/fallback'))->toBe('/fallback');
})->with([
    'post request' => [new Request(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/account'])],
    'protocol relative target' => [new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '//evil.test/path'])],
    'encoded protocol relative target' => [new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/%2Fevil.test/path'])],
    'backslash target' => [new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/\\evil.test/path'])],
    'control character target' => [new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/account%0d%0aX-Test:bad'])],
    'fragment target' => [new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/account#fragment'])],
]);

test('tampered intended session state falls back locally and is consumed', function () {
    $_SESSION['auth.intended'] = '//evil.test/path';
    $auth = new Authenticator();

    expect($auth->intended('/fallback'))->toBe('/fallback')
        ->and(Session::exists('auth.intended'))->toBeFalse();
});

test('intended redirect fallbacks must also be local paths', function () {
    (new Authenticator())->intended('//evil.test');
})->throws(InvalidArgumentException::class, 'must be a local absolute path');
