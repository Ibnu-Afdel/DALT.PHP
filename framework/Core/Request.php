<?php

declare(strict_types=1);

namespace Core;

class Request
{
    private const METHOD_OVERRIDES = ['PUT', 'PATCH', 'DELETE'];

    /** @var array<string, string> */
    private array $routeParameters = [];

    public function __construct(
        protected array $query = [],
        protected array $input = [],
        protected array $server = []
    ) {
    }

    public static function capture(): static
    {
        return new static($_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';
        $method = is_string($method) ? strtoupper($method) : 'GET';

        // Browsers submit HTML forms with GET or POST. Method spoofing lets a
        // POST form reach REST-style routes without allowing safe methods to
        // be silently rewritten by request data.
        if ($method !== 'POST') {
            return $method;
        }

        $override = $this->input['_method'] ?? null;
        $override = is_string($override) ? strtoupper($override) : null;

        return in_array($override, self::METHOD_OVERRIDES, true) ? $override : $method;
    }

    public function path(): string
    {
        $path = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return $path ?: '/';
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->input;
        }

        return $this->input[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->input);
    }

    /** @param array<string, string> $parameters */
    public function setRouteParameters(array $parameters): void
    {
        $this->routeParameters = $parameters;
    }

    public function route(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->routeParameters;
        }

        return $this->routeParameters[$key] ?? $default;
    }

    public function server(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->server;
        }

        return $this->server[$key] ?? null;
    }
}
