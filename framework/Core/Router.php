<?php

declare(strict_types=1);

namespace Core;

use Closure;
use LogicException;
use RuntimeException;

class Router
{
    /** @var list<Route> */
    protected array $routes = [];

    protected ?Request $request = null;

    private Container $container;

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? App::containerOrNull() ?? new Container();
    }

    public function add(string $method, string $uri, Closure|string $handler): self
    {
        $this->routes[] = new Route(strtoupper($method), $uri, $handler);

        return $this;
    }

    public function get(string $uri, Closure|string $handler): self
    {
        return $this->add('GET', $uri, $handler);
    }

    public function post(string $uri, Closure|string $handler): self
    {
        return $this->add('POST', $uri, $handler);
    }

    public function patch(string $uri, Closure|string $handler): self
    {
        return $this->add('PATCH', $uri, $handler);
    }

    public function put(string $uri, Closure|string $handler): self
    {
        return $this->add('PUT', $uri, $handler);
    }

    public function delete(string $uri, Closure|string $handler): self
    {
        return $this->add('DELETE', $uri, $handler);
    }

    /** @param string|list<string> $keys */
    public function only(string|array $keys): self
    {
        $route = $this->routes[array_key_last($this->routes)] ?? null;

        if ($route === null) {
            throw new LogicException('Register a route before attaching middleware.');
        }

        $route->setMiddleware($keys);

        return $this;
    }

    public function route(string $uri, string $method, ?Request $request = null): Response
    {
        $request ??= new Request(
            query: $_GET,
            input: $_POST,
            server: [
                ...$_SERVER,
                'REQUEST_METHOD' => strtoupper($method),
                'REQUEST_URI' => $uri,
            ],
        );
        $this->request = $request;
        $this->container->instance(Request::class, $request);

        foreach ($this->routes as $route) {
            if (strtoupper($method) !== $route->method()) {
                continue;
            }

            $parameters = $this->matchUri($route->uri(), $uri);
            if ($parameters === false) {
                continue;
            }

            $request->setRouteParameters($parameters);

            // Existing controller lessons read route parameters from $_GET.
            // Keep that bridge while Request::route() becomes the real API.
            foreach ($parameters as $key => $value) {
                $_GET[$key] = $value;
            }

            return (new Middleware\Middleware(container: $this->container))->run(
                $route->middleware(),
                $request,
                fn (Request $request): Response => Response::fromHandler(
                    fn () => $this->dispatch($route, $parameters, $request),
                ),
            );
        }

        abort(404);
    }

    /** @param array<string, string> $parameters */
    private function dispatch(Route $route, array $parameters, Request $request): mixed
    {
        $handler = $route->handler();

        if ($handler instanceof Closure) {
            return $this->dispatchClosure($handler, $parameters, $request);
        }

        return require $this->resolveControllerPath($handler);
    }

    /** @param array<string, string> $parameters */
    private function dispatchClosure(Closure $handler, array $parameters, Request $request): mixed
    {
        return $this->container->call($handler, $parameters);
    }

    private function resolveControllerPath(string $controller): string
    {
        $roots = [base_path('app/Http/controllers')];

        if (is_dir(base_path('.dalt'))) {
            $roots[] = base_path('.dalt/Http/controllers');
        }

        foreach ($roots as $root) {
            $rootPath = realpath($root);
            $controllerPath = realpath($root . '/' . $controller);

            if ($rootPath === false || $controllerPath === false || !is_file($controllerPath)) {
                continue;
            }

            if (str_starts_with($controllerPath, $rootPath . DIRECTORY_SEPARATOR)) {
                return $controllerPath;
            }
        }

        throw new RuntimeException("Controller not found: {$controller}");
    }

    /** @return array<string, string>|false */
    protected function matchUri(string $pattern, string $actual): array|false
    {
        if ($pattern === $actual) {
            return [];
        }

        preg_match_all(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            $pattern,
            $placeholders,
            PREG_OFFSET_CAPTURE,
        );

        $parameterNames = [];
        $regex = '';
        $offset = 0;

        foreach ($placeholders[0] as $index => [$placeholder, $position]) {
            $regex .= preg_quote(substr($pattern, $offset, $position - $offset), '#');
            $regex .= '([^/]+)';
            $parameterNames[] = $placeholders[1][$index][0];
            $offset = $position + strlen($placeholder);
        }

        $regex .= preg_quote(substr($pattern, $offset), '#');

        if (preg_match('#^' . $regex . '$#', $actual, $matches) !== 1) {
            return false;
        }

        array_shift($matches);

        return array_combine($parameterNames, $matches) ?: [];
    }

    public function previousUrl(): string
    {
        return $this->request?->server('HTTP_REFERER') ?? $_SERVER['HTTP_REFERER'] ?? '/';
    }
}
