<?php

declare(strict_types=1);

namespace Core;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

final class Container
{
    /** @var array<string, array{concrete: Closure|string, shared: bool}> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var list<string> */
    private array $buildStack = [];

    public function __construct()
    {
        $this->instances[self::class] = $this;
    }

    public function bind(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->register($abstract, $concrete ?? $abstract, false);
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->register($abstract, $concrete ?? $abstract, true);
    }

    public function instance(string $abstract, mixed $instance): void
    {
        unset($this->bindings[$abstract]);
        $this->instances[$abstract] = $instance;
    }

    public function has(string $abstract): bool
    {
        return array_key_exists($abstract, $this->instances)
            || array_key_exists($abstract, $this->bindings)
            || class_exists($abstract);
    }

    public function resolved(string $abstract): bool
    {
        return array_key_exists($abstract, $this->instances);
    }

    public function resolve(string $abstract): mixed
    {
        // array_key_exists is intentional: a shared null is still resolved.
        if (array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        if (in_array($abstract, $this->buildStack, true)) {
            $chain = implode(' -> ', [...$this->buildStack, $abstract]);

            throw new RuntimeException("Circular dependency detected: {$chain}");
        }

        $binding = $this->bindings[$abstract] ?? null;

        if ($binding === null && !class_exists($abstract)) {
            throw new RuntimeException(
                "Cannot resolve '{$abstract}': no binding or concrete class exists.",
            );
        }

        $this->buildStack[] = $abstract;

        try {
            $value = $binding === null
                ? $this->buildClass($abstract)
                : $this->build($binding['concrete'], $abstract);
        } finally {
            array_pop($this->buildStack);
        }

        if ($binding !== null && $binding['shared']) {
            $this->instances[$abstract] = $value;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $parameters Named values supplied by the caller.
     */
    public function call(Closure $callback, array $parameters = []): mixed
    {
        $arguments = [];

        foreach ((new ReflectionFunction($callback))->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                $values = $parameters[$parameter->getName()] ?? [];

                if (!is_array($values)) {
                    throw new RuntimeException(
                        "Variadic parameter \${$parameter->getName()} must receive an array.",
                    );
                }

                array_push($arguments, ...$values);
                continue;
            }

            $type = $parameter->getType();

            if (
                ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType)
                && array_key_exists($parameter->getName(), $parameters)
            ) {
                $arguments[] = $parameters[$parameter->getName()];
                continue;
            }

            $class = $this->parameterClass($parameter);

            if ($class !== null) {
                // Class types are dependencies; route values satisfy scalar or
                // untyped parameters by name below.
                $provided = $parameters[$parameter->getName()] ?? null;

                if (is_object($provided) && is_a($provided, $class)) {
                    $arguments[] = $provided;
                } elseif ($parameter->allowsNull() && !$this->has($class)) {
                    $arguments[] = null;
                } else {
                    $arguments[] = $this->resolve($class);
                }
                continue;
            }

            if (array_key_exists($parameter->getName(), $parameters)) {
                $arguments[] = $parameters[$parameter->getName()];
                continue;
            }

            $arguments[] = $this->parameterFallback($parameter, 'callable');
        }

        return $callback(...$arguments);
    }

    private function register(string $abstract, Closure|string $concrete, bool $shared): void
    {
        if ($abstract === '') {
            throw new RuntimeException('Container binding keys cannot be empty.');
        }

        unset($this->instances[$abstract]);
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    private function build(Closure|string $concrete, string $abstract): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        return $concrete === $abstract
            ? $this->buildClass($concrete)
            : $this->resolve($concrete);
    }

    private function buildClass(string $class): object
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException(
                "Cannot construct '{$class}': bind its interface or abstract class to an implementation.",
            );
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                continue;
            }

            $dependency = $this->parameterClass($parameter);

            if ($dependency !== null) {
                $arguments[] = $parameter->allowsNull() && !$this->has($dependency)
                    ? null
                    : $this->resolve($dependency);
                continue;
            }

            $arguments[] = $this->parameterFallback($parameter, $class);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private function parameterClass(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            throw new RuntimeException(
                "Cannot resolve \${$parameter->getName()}: union and intersection dependencies require an explicit value.",
            );
        }

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return match ($type->getName()) {
            'self' => $parameter->getDeclaringClass()?->getName(),
            'parent' => $parameter->getDeclaringClass()?->getParentClass()?->getName(),
            default => $type->getName(),
        };
    }

    private function parameterFallback(ReflectionParameter $parameter, string $context): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new RuntimeException(
            "Cannot resolve required parameter \${$parameter->getName()} while building {$context}.",
        );
    }
}
