<?php

namespace Core\Middleware;

class Middleware
{
    public const MAP = [
        'guest' => Guest::class,
        'auth' => Auth::class,
        'csrf' => Csrf::class,
    ];

    public static function resolve($keys)
    {
        if (!$keys) {
            return;
        }

        $middlewares = is_array($keys) ? $keys : [$keys];

        foreach ($middlewares as $key) {
            $middleware = static::MAP[$key] ?? false;
            if (!$middleware) {
                throw new \Exception("No Matching Middleware found for key '{$key}'");
            }
            (new $middleware)->handle();
        }
    }
}