<?php

declare(strict_types=1);

use Core\Response;

test('it stores response content status and headers without sending them', function () {
    $response = new Response('Created', 201, ['X-DALT' => 'framework']);

    expect($response->content())->toBe('Created')
        ->and($response->status())->toBe(201)
        ->and($response->headers())->toBe(['X-DALT' => 'framework']);
});

test('it creates html json and redirect responses', function () {
    $html = Response::html('<h1>DALT</h1>');
    $json = Response::json(['framework' => 'DALT']);
    $redirect = Response::redirect('/login', 303);

    expect($html->content())->toBe('<h1>DALT</h1>')
        ->and($html->headers()['Content-Type'])->toBe('text/html; charset=UTF-8')
        ->and($json->content())->toBe('{"framework":"DALT"}')
        ->and($json->headers()['Content-Type'])->toBe('application/json; charset=UTF-8')
        ->and($redirect->status())->toBe(303)
        ->and($redirect->headers())->toBe(['Location' => '/login']);
});

test('it normalizes supported handler results', function () {
    $existing = new Response('Existing', 202, ['X-DALT' => 'yes']);

    expect(Response::fromHandlerResult($existing))->toBe($existing)
        ->and(Response::fromHandlerResult('Hello')->content())->toBe('Hello')
        ->and(Response::fromHandlerResult(['ok' => true])->content())->toBe('{"ok":true}')
        ->and(Response::fromHandlerResult(null)->content())->toBe('');
});

test('legacy emitted output becomes the body while preserving explicit response metadata', function () {
    $response = Response::fromHandlerResult(
        new Response('ignored', 202, ['X-DALT' => 'yes']),
        '<h1>legacy output</h1>',
    );

    expect($response->content())->toBe('<h1>legacy output</h1>')
        ->and($response->status())->toBe(202)
        ->and($response->headers())->toBe(['X-DALT' => 'yes']);
});

test('it captures legacy output while executing a handler', function () {
    $response = Response::fromHandler(function (): string {
        echo '<p>legacy</p>';

        return 'ignored return value';
    });

    expect($response->content())->toBe('<p>legacy</p>')
        ->and($response->status())->toBe(200);
});

test('it cleans its output buffer when a handler throws', function () {
    $initialLevel = ob_get_level();

    try {
        Response::fromHandler(function (): never {
            echo 'must not leak';
            throw new RuntimeException('handler failed');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('handler failed');
    }

    expect(ob_get_level())->toBe($initialLevel);
});

test('it rejects unsupported handler results', function () {
    Response::fromHandlerResult(new stdClass());
})->throws(
    UnexpectedValueException::class,
    'Route handlers must return Response, string, array, or null; stdClass returned.',
);

test('it reports json encoding failures', function () {
    $recursive = [];
    $recursive['self'] = &$recursive;

    Response::json($recursive);
})->throws(JsonException::class);

test('it rejects invalid status codes and header injection', function () {
    expect(fn () => new Response(status: 99))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Response(headers: ['Bad Header' => 'value']))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Response(headers: ['X-Test' => "safe\r\nInjected: yes"]))
        ->toThrow(InvalidArgumentException::class);
});

test('it sends the selected status and body at the HTTP boundary', function () {
    $response = new Response('Sent once', 202);

    ob_start();
    $response->send();
    $body = ob_get_clean();

    expect(http_response_code())->toBe(202)
        ->and($body)->toBe('Sent once');
});
