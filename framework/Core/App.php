<?php

declare(strict_types=1);

namespace Core;

use Closure;
use LogicException;

final class App
{
    private static ?Container $container = null;

    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    public static function container(): Container
    {
        return self::$container ?? throw new LogicException(
            'The application container has not been bootstrapped.',
        );
    }

    public static function containerOrNull(): ?Container
    {
        return self::$container;
    }

    public static function forgetContainer(): void
    {
        self::$container = null;
    }

    public static function bind(string $abstract, Closure|string|null $concrete = null): void
    {
        self::container()->bind($abstract, $concrete);
    }

    public static function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        self::container()->singleton($abstract, $concrete);
    }

    public static function instance(string $abstract, mixed $instance): void
    {
        self::container()->instance($abstract, $instance);
    }

    public static function resolve(string $abstract): mixed
    {
        return self::container()->resolve($abstract);
    }
}
