<?php

declare(strict_types=1);

use Core\App;
use Core\Container;
use Core\Database;
use Core\Response;
use Core\Session;
use Core\ValidationException;

final class ScaffoldAuthenticationDatabase extends Database
{
    /** @var list<array{query: string, params: array<string, mixed>}> */
    public array $queries = [];

    public function __construct()
    {
    }

    /** @param array<string, mixed> $params */
    public function query($query, $params = []): static
    {
        $this->queries[] = ['query' => $query, 'params' => $params];

        return $this;
    }

    public function find(): false
    {
        return false;
    }
}

function installScaffoldDatabase(Database $database): void
{
    $container = new Container();
    $container->instance(Database::class, $database);
    App::setContainer($container);
}

test('login scaffold turns non-string form values into validation errors', function () {
    $_POST = ['email' => ['array'], 'password' => ['array']];

    require base_path('.dalt/stubs/auth/Http/controllers/session/store.php');
})->throws(ValidationException::class, 'The given data was invalid.');

test('registration scaffold turns non-string form values into validation errors', function () {
    $_POST = [
        'name' => ['array'],
        'email' => ['array'],
        'password' => ['array'],
        'password_confirmation' => ['array'],
    ];

    require base_path('.dalt/stubs/auth/Http/controllers/registration/store.php');
})->throws(ValidationException::class, 'The given data was invalid.');

test('registration scaffold stores a verifiable default password hash', function () {
    $database = new ScaffoldAuthenticationDatabase();
    installScaffoldDatabase($database);
    $_POST = [
        'name' => 'Dalt Learner',
        'email' => 'learner@example.com',
        'password' => 'correct horse',
        'password_confirmation' => 'correct horse',
    ];

    $response = require base_path('.dalt/stubs/auth/Http/controllers/registration/store.php');
    $insert = $database->queries[1];

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->headers()['Location'])->toBe('/login')
        ->and($insert['params']['password'])->not->toBe('correct horse')
        ->and(password_verify('correct horse', $insert['params']['password']))->toBeTrue()
        ->and(Session::get('success'))->toBe('Registration successful. You can now log in.');
});

test('registration scaffold enforces its documented password byte range', function (string $password) {
    $_POST = [
        'name' => 'Dalt Learner',
        'email' => 'learner@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ];

    require base_path('.dalt/stubs/auth/Http/controllers/registration/store.php');
})->with([
    'too short' => '1234567',
    'too long' => str_repeat('a', 73),
])->throws(ValidationException::class, 'The given data was invalid.');
