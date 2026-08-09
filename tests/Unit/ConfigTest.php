<?php

declare(strict_types=1);

use Core\Config;

test('configuration supports dot lookup defaults and typed reads', function () {
    $config = new Config([
        'app' => [
            'name' => 'DALT',
            'debug' => false,
            'retries' => 3,
            'features' => ['learning'],
            'nullable' => null,
        ],
    ]);

    expect($config->get())->toBeArray()
        ->and($config->get('app.name'))->toBe('DALT')
        ->and($config->get('app.missing', 'fallback'))->toBe('fallback')
        ->and($config->get('app.nullable', 'fallback'))->toBeNull()
        ->and($config->string('app.name'))->toBe('DALT')
        ->and($config->boolean('app.debug'))->toBeFalse()
        ->and($config->integer('app.retries'))->toBe(3)
        ->and($config->array('app.features'))->toBe(['learning']);
});

test('typed configuration reads report the actual invalid type', function () {
    (new Config(['app' => ['debug' => 'false']]))->boolean('app.debug');
})->throws(
    UnexpectedValueException::class,
    "Configuration 'app.debug' must be boolean; string found.",
);

test('project configuration files load in deterministic order with normalized values', function () {
    $_ENV['APP_ENV'] = 'testing';
    $_ENV['APP_DEBUG'] = 'false';
    $_ENV['DB_PORT'] = '5433';

    $config = Config::load(base_path('config'));

    expect(array_keys($config->get()))->toBe(['app', 'database'])
        ->and($config->string('app.env'))->toBe('testing')
        ->and($config->boolean('app.debug'))->toBeFalse()
        ->and($config->integer('database.database.port'))->toBe(5433);
});

test('configuration loading rejects a missing directory', function () {
    Config::load(base_path('config-that-does-not-exist'));
})->throws(RuntimeException::class, 'Configuration directory not found:');

test('configuration loading rejects files that do not return arrays', function () {
    Config::load(base_path('tests/Fixtures/config-invalid'));
})->throws(UnexpectedValueException::class, 'Configuration file must return an array:');
