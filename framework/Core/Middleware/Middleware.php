<?php

declare(strict_types=1);

namespace Core\Middleware;

use Closure;
use Core\App;
use Core\Container;
use Core\Request;
use Core\Response;
use RuntimeException;
use Throwable;

final class Middleware
{
    /** @var array<string, class-string<MiddlewareInterface>> */
    public const MAP = [
        'guest' => Guest::class,
        'auth' => Auth::class,
        'csrf' => Csrf::class,
    ];

    /** @var array<string, class-string<MiddlewareInterface>> */
    private array $aliases;

    private Container $container;

    /** @param array<string, class-string<MiddlewareInterface>> $aliases */
    public function __construct(array $aliases = self::MAP, ?Container $container = null)
    {
        $this->aliases = $aliases;
        $this->container = $container ?? App::containerOrNull() ?? new Container();
    }

    /**
     * @param string|list<string>|null $keys
     * @param Closure(Request): Response $destination
     */
    public function run(string|array|null $keys, Request $request, Closure $destination): Response
    {
        $layers = $keys === null ? [] : (is_array($keys) ? $keys : [$keys]);
        $pipeline = $destination;

        // Wrap from the inside out. The first declared middleware therefore
        // sees the request first and the response last.
        foreach (array_reverse($layers) as $key) {
            $middleware = $this->resolve($key);
            $next = $pipeline;
            $pipeline = static fn (Request $request): Response => $middleware->handle($request, $next);
        }

        return $pipeline($request);
    }

    private function resolve(string $key): MiddlewareInterface
    {
        $class = $this->aliases[$key] ?? $key;

        if (!class_exists($class)) {
            throw new RuntimeException("No middleware found for '{$key}'.");
        }

        if (!is_a($class, MiddlewareInterface::class, true)) {
            throw new RuntimeException(
                "Middleware '{$class}' must implement " . MiddlewareInterface::class . '.',
            );
        }

        try {
            return $this->container->resolve($class);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to construct middleware '{$class}': {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }
}
