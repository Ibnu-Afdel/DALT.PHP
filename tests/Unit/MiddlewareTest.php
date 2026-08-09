<?php

declare(strict_types=1);

use Core\Middleware\Middleware;
use Core\Middleware\MiddlewareInterface;
use Core\Container;
use Core\Request;
use Core\Response;

final class MiddlewareTrace
{
    /** @var list<string> */
    public static array $events = [];
}

final class FirstPipelineMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        MiddlewareTrace::$events[] = 'first:before';
        $response = $next($request);
        MiddlewareTrace::$events[] = 'first:after';

        return $response->withHeader('X-First', 'visited');
    }
}

final class SecondPipelineMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        MiddlewareTrace::$events[] = 'second:before';
        $response = $next($request);
        MiddlewareTrace::$events[] = 'second:after';

        return $response->withContent($response->content() . ':second');
    }
}

final class ShortCircuitMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        MiddlewareTrace::$events[] = 'short-circuit';

        return Response::text('stopped', 403);
    }
}

final class ConstructorMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $value)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        return $next($request)->withContent($this->value);
    }
}

final class MiddlewareDependency
{
}

final class InjectedMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly MiddlewareDependency $dependency)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        return $next($request)->withHeader(
            'X-Dependency',
            $this->dependency::class,
        );
    }
}

final class NotMiddleware
{
}

beforeEach(function () {
    MiddlewareTrace::$events = [];
});

test('an empty middleware stack reaches the destination', function () {
    $response = (new Middleware())->run(
        null,
        new Request(),
        fn (Request $request): Response => Response::text('destination'),
    );

    expect($response->content())->toBe('destination');
});

test('middleware runs inward in declaration order and outward in reverse order', function () {
    $pipeline = new Middleware([
        'first' => FirstPipelineMiddleware::class,
        'second' => SecondPipelineMiddleware::class,
    ]);

    $response = $pipeline->run(
        ['first', 'second'],
        new Request(),
        function (Request $request): Response {
            MiddlewareTrace::$events[] = 'handler';

            return Response::text('response');
        },
    );

    expect(MiddlewareTrace::$events)->toBe([
        'first:before',
        'second:before',
        'handler',
        'second:after',
        'first:after',
    ])->and($response->content())->toBe('response:second')
        ->and($response->headers()['X-First'])->toBe('visited');
});

test('middleware may short-circuit before inner layers and the destination', function () {
    $pipeline = new Middleware([
        'stop' => ShortCircuitMiddleware::class,
        'second' => SecondPipelineMiddleware::class,
    ]);

    $response = $pipeline->run(
        ['stop', 'second'],
        new Request(),
        function (Request $request): Response {
            MiddlewareTrace::$events[] = 'handler';

            return Response::text('unreachable');
        },
    );

    expect(MiddlewareTrace::$events)->toBe(['short-circuit'])
        ->and($response->status())->toBe(403)
        ->and($response->content())->toBe('stopped');
});

test('a middleware class name can be used without a framework alias', function () {
    $response = (new Middleware())->run(
        FirstPipelineMiddleware::class,
        new Request(),
        fn (Request $request): Response => Response::text('ok'),
    );

    expect(MiddlewareTrace::$events)->toBe(['first:before', 'first:after'])
        ->and($response->headers()['X-First'])->toBe('visited');
});

test('an unknown middleware alias fails clearly', function () {
    (new Middleware())->run(
        'missing',
        new Request(),
        fn (Request $request): Response => Response::text('unreachable'),
    );
})->throws(RuntimeException::class, "No middleware found for 'missing'.");

test('a resolved class must implement the middleware contract', function () {
    (new Middleware(['invalid' => NotMiddleware::class]))->run(
        'invalid',
        new Request(),
        fn (Request $request): Response => Response::text('unreachable'),
    );
})->throws(RuntimeException::class, 'must implement Core\\Middleware\\MiddlewareInterface');

test('middleware construction failures preserve their container cause', function () {
    (new Middleware(['constructor' => ConstructorMiddleware::class]))->run(
        'constructor',
        new Request(),
        fn (Request $request): Response => Response::text('unreachable'),
    );
})->throws(
    RuntimeException::class,
    "Unable to construct middleware 'ConstructorMiddleware': Cannot resolve required parameter \$value",
);

test('middleware constructor dependencies are built by the shared container', function () {
    $response = (new Middleware(
        aliases: ['injected' => InjectedMiddleware::class],
        container: new Container(),
    ))->run(
        'injected',
        new Request(),
        fn (Request $request): Response => Response::text('handled'),
    );

    expect($response->content())->toBe('handled')
        ->and($response->headers()['X-Dependency'])->toBe(MiddlewareDependency::class);
});

test('auth middleware redirects guests and does not call the destination', function () {
    $called = false;

    $response = (new Middleware())->run(
        'auth',
        new Request(),
        function (Request $request) use (&$called): Response {
            $called = true;

            return Response::text('private');
        },
    );

    expect($called)->toBeFalse()
        ->and($response->status())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/login');
});

test('auth middleware passes authenticated requests onward', function () {
    $_SESSION['user'] = ['id' => 1];

    $response = (new Middleware())->run(
        'auth',
        new Request(),
        fn (Request $request): Response => Response::text('private'),
    );

    expect($response->content())->toBe('private');
});

test('guest middleware redirects authenticated users', function () {
    $_SESSION['user'] = ['id' => 1];

    $response = (new Middleware())->run(
        'guest',
        new Request(),
        fn (Request $request): Response => Response::text('guest page'),
    );

    expect($response->status())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/');
});

test('guest middleware passes unauthenticated requests onward', function () {
    $response = (new Middleware())->run(
        'guest',
        new Request(),
        fn (Request $request): Response => Response::text('guest page'),
    );

    expect($response->content())->toBe('guest page');
});

test('csrf middleware bypasses safe request methods', function (string $method) {
    $response = (new Middleware())->run(
        'csrf',
        new Request(server: ['REQUEST_METHOD' => $method]),
        fn (Request $request): Response => Response::text('safe'),
    );

    expect($response->content())->toBe('safe');
})->with(['GET', 'HEAD', 'OPTIONS']);

test('csrf middleware accepts a matching form token', function () {
    $_SESSION['_csrf'] = 'known-token';

    $response = (new Middleware())->run(
        'csrf',
        new Request(
            input: ['_token' => 'known-token'],
            server: ['REQUEST_METHOD' => 'POST'],
        ),
        fn (Request $request): Response => Response::text('changed'),
    );

    expect($response->content())->toBe('changed');
});

test('csrf middleware accepts a matching header token', function () {
    $_SESSION['_csrf'] = 'known-token';

    $response = (new Middleware())->run(
        'csrf',
        new Request(server: [
            'REQUEST_METHOD' => 'POST',
            'HTTP_X_CSRF_TOKEN' => 'known-token',
        ]),
        fn (Request $request): Response => Response::text('changed'),
    );

    expect($response->content())->toBe('changed');
});

test('csrf middleware rejects absent mismatched and non-string tokens', function (
    mixed $sessionToken,
    mixed $requestToken,
) {
    if ($sessionToken !== null) {
        $_SESSION['_csrf'] = $sessionToken;
    }

    $response = (new Middleware())->run(
        'csrf',
        new Request(
            input: $requestToken === null ? [] : ['_token' => $requestToken],
            server: ['REQUEST_METHOD' => 'POST'],
        ),
        fn (Request $request): Response => Response::text('unreachable'),
    );

    expect($response->status())->toBe(419)
        ->and($response->content())->toBe('CSRF token mismatch')
        ->and($response->headers()['Content-Type'])->toBe('text/plain; charset=UTF-8');
})->with([
    'both absent' => [null, null],
    'mismatch' => ['known-token', 'wrong-token'],
    'array session token' => [['known-token'], 'known-token'],
    'array request token' => ['known-token', ['known-token']],
    'empty tokens' => ['', ''],
]);

test('csrf uses the centralized method override policy', function () {
    $_SESSION['_csrf'] = 'known-token';
    $request = new Request(
        input: ['_method' => 'DELETE'],
        server: ['REQUEST_METHOD' => 'POST'],
    );

    $response = (new Middleware())->run(
        'csrf',
        $request,
        fn (Request $request): Response => Response::text('unreachable'),
    );

    expect($request->method())->toBe('DELETE')
        ->and($response->status())->toBe(419);
});
