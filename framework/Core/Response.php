<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;
use JsonException;
use Throwable;
use UnexpectedValueException;

final readonly class Response
{
    /** @var array<string, string> */
    private array $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private string $content = '',
        private int $status = 200,
        array $headers = [],
    ) {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException("Invalid HTTP status code: {$status}");
        }

        foreach ($headers as $name => $value) {
            if (!is_string($name) || preg_match('/^[A-Za-z0-9-]+$/D', $name) !== 1) {
                throw new InvalidArgumentException('HTTP header names may contain only letters, numbers, and hyphens.');
            }

            if (!is_string($value) || str_contains($value, "\r") || str_contains($value, "\n")) {
                throw new InvalidArgumentException("Invalid value for HTTP header: {$name}");
            }
        }

        $this->headers = $headers;
    }

    /** @param array<string, string> $headers */
    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        return new self(
            $content,
            $status,
            ['Content-Type' => 'text/html; charset=UTF-8', ...$headers],
        );
    }

    /**
     * @param array<array-key, mixed> $data
     * @param array<string, string> $headers
     *
     * @throws JsonException
     */
    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        return new self(
            json_encode($data, JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8', ...$headers],
        );
    }

    /** @param array<string, string> $headers */
    public static function redirect(string $location, int $status = 302, array $headers = []): self
    {
        return new self('', $status, ['Location' => $location, ...$headers]);
    }

    public static function fromHandlerResult(mixed $result, string $output = ''): self
    {
        if ($output !== '') {
            if ($result instanceof self) {
                return new self($output, $result->status(), $result->headers());
            }

            return self::html($output);
        }

        return match (true) {
            $result instanceof self => $result,
            is_string($result) => self::html($result),
            is_array($result) => self::json($result),
            $result === null => new self(),
            default => throw new UnexpectedValueException(sprintf(
                'Route handlers must return Response, string, array, or null; %s returned.',
                get_debug_type($result),
            )),
        };
    }

    public static function fromHandler(callable $handler): self
    {
        $initialBufferLevel = ob_get_level();
        ob_start();

        try {
            $result = $handler();
            $output = (string) ob_get_clean();
        } catch (Throwable $exception) {
            while (ob_get_level() > $initialBufferLevel) {
                ob_end_clean();
            }

            throw $exception;
        }

        return self::fromHandlerResult($result, $output);
    }

    public function content(): string
    {
        return $this->content;
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }

        echo $this->content;
    }
}
