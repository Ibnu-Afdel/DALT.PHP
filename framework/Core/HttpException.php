<?php

namespace Core;

class HttpException extends \RuntimeException
{
    public int $statusCode;

    public function __construct(int $code, string $message = '')
    {
        $this->statusCode = $code;
        parent::__construct($message ?: "HTTP {$code}", $code);
    }
}
