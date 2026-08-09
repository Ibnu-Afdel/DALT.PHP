<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;

class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
    )
    {
        if ($statusCode < 400 || $statusCode > 599) {
            throw new InvalidArgumentException(
                "HTTP exception status must be between 400 and 599; {$statusCode} given.",
            );
        }

        parent::__construct($message !== '' ? $message : "HTTP {$statusCode}", $statusCode);
    }
}
