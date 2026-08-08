<?php

declare(strict_types=1);

namespace Core;

use Closure;
use InvalidArgumentException;

final class Route
{
    /** @var string|list<string>|null */
    private string|array|null $middleware = null;

    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly Closure|string $handler,
    ) {
        if (is_string($handler) && self::isUnsafeControllerPath($handler)) {
            throw new InvalidArgumentException(
                'Controller handlers must be non-empty relative paths without parent-directory segments.',
            );
        }
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function handler(): Closure|string
    {
        return $this->handler;
    }

    /** @param string|list<string> $middleware */
    public function setMiddleware(string|array $middleware): void
    {
        $this->middleware = $middleware;
    }

    /** @return string|list<string>|null */
    public function middleware(): string|array|null
    {
        return $this->middleware;
    }

    private static function isUnsafeControllerPath(string $path): bool
    {
        return $path === ''
            || str_contains($path, "\0")
            || str_contains($path, '\\')
            || str_starts_with($path, '/')
            || preg_match('#(^|/)\.\.(/|$)#', $path) === 1
            || preg_match('/^[A-Za-z]:\//', $path) === 1;
    }
}
