<?php

declare(strict_types=1);

namespace Tests\Support;

final readonly class ApplicationResponse
{
    /**
     * @param list<string> $headers
     * @param array{type: int, message: string, file: string, line: int}|null $error
     */
    public function __construct(
        public int $exitCode,
        public int $statusCode,
        public string $body,
        public array $headers,
        public ?array $error,
        public string $stderr,
    ) {
    }
}
