<?php

declare(strict_types=1);

use Tests\Support\ApplicationTestClient;

test('the front controller serves the application welcome route', function () {
    $response = (new ApplicationTestClient())->request('GET', '/');

    expect($response->exitCode)->toBe(0)
        ->and($response->statusCode)->toBe(200)
        ->and($response->error)->toBeNull()
        ->and($response->stderr)->toBe('')
        ->and($response->body)->toContain('<title>DALT.PHP</title>');
});

test('the front controller renders an http exception as a response', function () {
    $response = (new ApplicationTestClient())->request('GET', '/missing-route');

    expect($response->exitCode)->toBe(0)
        ->and($response->statusCode)->toBe(404)
        ->and($response->error)->toBeNull()
        ->and($response->stderr)->toBe('')
        ->and($response->body)->toBe('<h1>404</h1><p>Not Found</p>');
});
