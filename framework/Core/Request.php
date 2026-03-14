<?php

namespace Core;

class Request
{
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
        return strtoupper($this->input['_method'] ?? $this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $path = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return $path ?: '/';
    }

    public function query($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function input($key = null, $default = null)
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

    public function server($key = null)
    {
        if ($key === null) {
            return $this->server;
        }

        return $this->server[$key] ?? null;
    }
}