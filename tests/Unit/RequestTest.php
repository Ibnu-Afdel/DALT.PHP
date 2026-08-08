<?php

declare(strict_types=1);

use Core\Request;

test('it captures request data from PHP superglobals', function () {
    $_GET = ['page' => '2'];
    $_POST = ['title' => 'DALT'];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/posts?page=2';

    $request = Request::capture();

    expect($request->method())->toBe('POST')
        ->and($request->path())->toBe('/posts')
        ->and($request->query())->toBe(['page' => '2'])
        ->and($request->input())->toBe(['title' => 'DALT']);
});

test('it normalizes methods and supports valid form method overrides', function (string $override) {
    $request = new Request(
        input: ['_method' => strtolower($override)],
        server: ['REQUEST_METHOD' => 'post'],
    );

    expect($request->method())->toBe($override);
})->with(['PUT', 'PATCH', 'DELETE']);

test('it only applies a method override to a real post request', function () {
    $request = new Request(
        input: ['_method' => 'DELETE'],
        server: ['REQUEST_METHOD' => 'GET'],
    );

    expect($request->method())->toBe('GET');
});

test('it ignores method overrides the router does not support', function (mixed $override) {
    $request = new Request(
        input: ['_method' => $override],
        server: ['REQUEST_METHOD' => 'POST'],
    );

    expect($request->method())->toBe('POST');
})->with([
    'safe method' => 'GET',
    'unknown method' => 'CUSTOM',
    'non-string value' => [['DELETE']],
]);

test('it returns the path without its query string', function (string $uri, string $expected) {
    $request = new Request(server: ['REQUEST_URI' => $uri]);

    expect($request->path())->toBe($expected);
})->with([
    'root' => ['/', '/'],
    'nested path' => ['/users/42/edit', '/users/42/edit'],
    'query string' => ['/search?q=dalt', '/search'],
    'empty URI' => ['', '/'],
]);

test('it keeps query and body input separate while body values win in all input', function () {
    $request = new Request(
        query: ['page' => '2', 'shared' => 'query'],
        input: ['title' => 'DALT', 'shared' => 'body'],
    );

    expect($request->query('page'))->toBe('2')
        ->and($request->query('missing', 'fallback'))->toBe('fallback')
        ->and($request->input('title'))->toBe('DALT')
        ->and($request->input('missing', 'fallback'))->toBe('fallback')
        ->and($request->all())->toBe([
            'page' => '2',
            'shared' => 'body',
            'title' => 'DALT',
        ]);
});

test('it exposes captured server data instead of rereading the superglobal', function () {
    $request = new Request(server: ['HTTP_REFERER' => '/from']);
    $_SERVER['HTTP_REFERER'] = '/changed';

    expect($request->server('HTTP_REFERER'))->toBe('/from')
        ->and($request->server('missing'))->toBeNull()
        ->and($request->server())->toBe(['HTTP_REFERER' => '/from']);
});

test('it exposes route parameters separately from query and body input', function () {
    $request = new Request(query: ['id' => 'query'], input: ['id' => 'body']);
    $request->setRouteParameters(['id' => 'route']);

    expect($request->route('id'))->toBe('route')
        ->and($request->route('missing', 'fallback'))->toBe('fallback')
        ->and($request->route())->toBe(['id' => 'route'])
        ->and($request->query('id'))->toBe('query')
        ->and($request->input('id'))->toBe('body');
});
