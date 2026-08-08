<?php

declare(strict_types=1);

use Core\HttpException;
use Core\Middleware\MiddlewareInterface;
use Core\Request;
use Core\Response;
use Core\Router;

final class RouterMiddlewareTrace
{
    /** @var list<string> */
    public static array $events = [];
}

final class RouterResponseMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        RouterMiddlewareTrace::$events[] = 'middleware:before';
        $response = $next($request);
        RouterMiddlewareTrace::$events[] = 'middleware:after';

        return $response->withHeader('X-Route-Middleware', 'visited');
    }
}

test('it dispatches closure handlers for every supported http verb', function (string $method) {
    $router = new Router();
    $router->{strtolower($method)}('/endpoint', fn () => $method);

    $response = $router->route('/endpoint', $method, new Request());

    expect($response->content())->toBe($method);
})->with(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);

test('it normalizes closure output and returned values into responses', function () {
    $router = new Router();
    $router->get('/output', function (): void {
        echo '<h1>output</h1>';
    });
    $router->get('/json', fn () => ['ok' => true]);
    $router->get('/response', fn () => new Response('accepted', 202));

    expect($router->route('/output', 'GET')->content())->toBe('<h1>output</h1>')
        ->and($router->route('/json', 'GET')->content())->toBe('{"ok":true}')
        ->and($router->route('/response', 'GET')->status())->toBe(202);
});

test('it injects the captured request and named route parameters into closures', function () {
    $router = new Router();
    $request = new Request(query: ['source' => 'query']);
    $router->get(
        '/users/{user}/posts/{post}',
        fn (Request $request, string $post, string $user) => [
            'user' => $user,
            'post' => $post,
            'source' => $request->query('source'),
        ],
    );

    $response = $router->route('/users/5/posts/42', 'GET', $request);

    expect($response->content())->toBe('{"user":"5","post":"42","source":"query"}')
        ->and($request->route())->toBe(['user' => '5', 'post' => '42']);
});

test('route parameters stay separate from captured query input', function () {
    $_GET = ['id' => 'from-query'];
    $request = Request::capture();
    $router = new Router();
    $router->get('/posts/{id}', fn (Request $request) => [
        'route' => $request->route('id'),
        'query' => $request->query('id'),
        'legacy' => $_GET['id'],
    ]);

    $response = $router->route('/posts/42', 'GET', $request);

    expect($response->content())->toBe(
        '{"route":"42","query":"from-query","legacy":"42"}',
    );
});

test('it treats regex characters in route patterns as literal text', function () {
    $router = new Router();
    $router->get('/files/{name}.json', fn (string $name) => $name);

    expect($router->route('/files/report.json', 'GET')->content())->toBe('report');

    $router->route('/files/reportXjson', 'GET');
})->throws(HttpException::class);

test('the first matching route wins', function () {
    $router = new Router();
    $router->get('/posts/{id}', fn (string $id) => "generic:{$id}");
    $router->get('/posts/create', fn () => 'specific');

    expect($router->route('/posts/create', 'GET')->content())->toBe('generic:create');
});

test('it dispatches controller files through the response boundary', function () {
    $router = new Router();
    $router->get('/', 'welcome.php');

    $response = $router->route('/', 'GET', new Request());

    expect($response->status())->toBe(200)
        ->and($response->content())->toContain('<title>DALT.PHP</title>');
});

test('it falls back to optional dalt controllers after checking the application root', function () {
    $router = new Router();
    $router->get('/learn/start', 'learn/start.php');

    $response = $router->route('/learn/start', 'GET', new Request());

    expect($response->status())->toBe(200)
        ->and($response->content())->toContain('<title>DALT.PHP');
});

test('it fails clearly for missing controllers', function () {
    $router = new Router();
    $router->get('/missing', 'missing.php');

    $router->route('/missing', 'GET');
})->throws(RuntimeException::class, 'Controller not found: missing.php');

test('it rejects controller paths that can escape their root', function (string $path) {
    (new Router())->get('/unsafe', $path);
})->with([
    '../bootstrap.php',
    '/tmp/controller.php',
    'nested\\controller.php',
])->throws(InvalidArgumentException::class);

test('it fails clearly when middleware is attached before any route', function () {
    (new Router())->only('auth');
})->throws(LogicException::class, 'Register a route before attaching middleware.');

test('it resolves middleware attached to the most recently registered route', function () {
    $router = new Router();
    $router->get('/guarded', fn () => 'guarded')->only('missing-alias');

    $router->route('/guarded', 'GET');
})->throws(RuntimeException::class, "No middleware found for 'missing-alias'.");

test('route middleware wraps closure dispatch and receives its response', function () {
    RouterMiddlewareTrace::$events = [];
    $router = new Router();
    $router->get('/guarded', function (Request $request): string {
        RouterMiddlewareTrace::$events[] = 'handler';

        return 'guarded';
    })->only(RouterResponseMiddleware::class);

    $response = $router->route('/guarded', 'GET');

    expect(RouterMiddlewareTrace::$events)->toBe([
        'middleware:before',
        'handler',
        'middleware:after',
    ])
        ->and($response->content())->toBe('guarded')
        ->and($response->headers()['X-Route-Middleware'])->toBe('visited');
});

test('route middleware wraps legacy controller output', function () {
    RouterMiddlewareTrace::$events = [];
    $router = new Router();
    $router->get('/', 'welcome.php')->only(RouterResponseMiddleware::class);

    $response = $router->route('/', 'GET');

    expect(RouterMiddlewareTrace::$events)->toBe([
        'middleware:before',
        'middleware:after',
    ])->and($response->content())->toContain('<title>DALT.PHP</title>')
        ->and($response->headers()['X-Route-Middleware'])->toBe('visited');
});

test('router always provides a request to typed closure handlers', function () {
    $router = new Router();
    $router->post('/capture', fn (Request $request) => [
        'method' => $request->method(),
        'path' => $request->path(),
    ]);

    $response = $router->route('/capture', 'POST');

    expect(json_decode($response->content(), true, flags: JSON_THROW_ON_ERROR))->toBe([
        'method' => 'POST',
        'path' => '/capture',
    ]);
});

test('it fails clearly when a required closure argument cannot be resolved', function () {
    $router = new Router();
    $router->get('/hello', fn (string $missing) => $missing);

    $router->route('/hello', 'GET');
})->throws(RuntimeException::class, 'Cannot resolve route closure parameter $missing.');

test('it throws an http 404 when no uri and method combination matches', function () {
    $router = new Router();
    $router->get('/known', fn () => 'known');

    try {
        $router->route('/known', 'POST');
    } catch (HttpException $exception) {
        expect($exception->statusCode)->toBe(404)
            ->and($exception->getMessage())->toBe('Not Found');

        return;
    }

    $this->fail('Expected an HttpException for the unmatched route.');
});
