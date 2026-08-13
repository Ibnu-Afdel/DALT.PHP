<?php

declare(strict_types=1);

namespace Core;

use Throwable;

final readonly class ExceptionHandler
{
    public function __construct(private bool $debug)
    {
    }

    public function report(Throwable $exception): void
    {
        if ($exception instanceof HttpException && $exception->statusCode < 500) {
            return;
        }

        app_log(sprintf(
            "%s: %s in %s:%d\n%s",
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString(),
        ));
    }

    public function render(Throwable $exception): Response
    {
        $status = 500;

        return Response::html(sprintf(
            '<h1>%d</h1><p><strong>%s:</strong> %s</p><p>%s:%d</p><pre>%s</pre>',
            $status,
            $this->escape($exception::class),
            $this->escape($exception->getMessage()),
            $this->escape($exception->getFile()),
            $exception->getLine(),
            $this->escape($exception->getTraceAsString()),
        ), $status);
    }

    private function errorResponse(int $status, string $message): Response
    {
        return Response::html(sprintf(
            '<h1>%d</h1><p>%s</p>',
            $status,
            $this->escape($message),
        ), $status);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
