<?php

namespace Core;
use Core\Middleware\Auth;
use Core\Middleware\Guest;
use Core\Middleware\Middleware;

class Router
{

    protected $routes = [];

    public function add($method, $uri, $controller)
    {
        $this->routes[] = [
            'uri' => $uri,
            'controller' => $controller,
            'method' => $method,
            'middleware' => null,
        ];
        return $this;
    }
    public function get($uri, $controller)
    {
        return $this->add('GET', $uri, $controller);
    }

    public function post($uri, $controller)
    {
        return $this->add('POST', $uri, $controller);
    }

    public function patch($uri, $controller)
    {
        return $this->add('PATCH', $uri, $controller);
    }

    public function put($uri, $controller)
    {
        return $this->add('PUT', $uri, $controller);
    }

    public function delete($uri, $controller)
    {
        return $this->add('DELETE', $uri, $controller);
    }

    public function only($key)
    {
        $this->routes[array_key_last($this->routes)]['middleware'] = $key;
        return $this;
    }

    public function route($uri, $method)
    {
        foreach ($this->routes as $route) {
            if (strtoupper($method) !== $route['method']) {
                continue;
            }

            $params = $this->matchUri($route['uri'], $uri);
            if ($params === false) {
                continue;
            }

            Middleware::resolve($route['middleware']);

            // Inject parameters into $_GET so controllers can access via 
            // $_GET['id'] or similar without extra abstractions
            foreach ($params as $key => $value) {
                $_GET[$key] = $value;
            }

            return require base_path('Http/controllers/' . $route['controller']);
        }
        $this->abort();
    }

    protected function matchUri(string $pattern, string $actual)
    {
        // Exact match fast-path
        if ($pattern === $actual) {
            return [];
        }

        // Convert /path/{id}/edit to regex ^/path/([^/]+)/edit$
        $paramNames = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '([^/]+)';
        }, $pattern);

        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $actual, $matches)) {
            array_shift($matches); // remove full match
            return array_combine($paramNames, $matches) ?: [];
        }

        return false;
    }

    public function previousUrl()
    {
        return $_SERVER['HTTP_REFERER'];
    }

    protected function abort($code = 404)
    {
        http_response_code($code);

        require base_path("resources/views/status/{$code}.php");
        die();
    }

}



