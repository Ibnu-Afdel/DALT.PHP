<?php

declare(strict_types=1);

use Core\ExceptionHandler;
use Core\HttpException;

test('debug error responses escape diagnostic exception details', function () {
    $exception = new RuntimeException('<script>alert("secret")</script>');
    $response = (new ExceptionHandler(debug: true))->render($exception);

    expect($response->status())->toBe(500)
        ->and($response->headers()['Content-Type'])->toBe('text/html; charset=UTF-8')
        ->and($response->content())->toContain('RuntimeException')
        ->and($response->content())->toContain('&lt;script&gt;alert(&quot;secret&quot;)&lt;/script&gt;')
        ->and($response->content())->toContain('ExceptionHandlerTest.php')
        ->and($response->content())->not->toContain('<script>alert');
});

test('production error responses do not disclose server exception details', function () {
    $response = (new ExceptionHandler(debug: false))->render(
        new RuntimeException('database password is secret'),
    );

    expect($response->status())->toBe(500)
        ->and($response->content())->toBe('<h1>500</h1><p>Internal Server Error</p>')
        ->and($response->content())->not->toContain('password');
});

test('http exceptions preserve client errors and hide production server details', function () {
    $handler = new ExceptionHandler(debug: false);
    $notFound = $handler->render(new HttpException(404, 'Article not found'));
    $unavailable = $handler->render(new HttpException(503, 'Database host is secret'));

    expect($notFound->status())->toBe(404)
        ->and($notFound->content())->toBe('<h1>404</h1><p>Article not found</p>')
        ->and($unavailable->status())->toBe(503)
        ->and($unavailable->content())->toBe('<h1>503</h1><p>Internal Server Error</p>');
});

test('exception reporting ignores client errors and records server diagnostics', function () {
    $logPath = sys_get_temp_dir() . '/dalt-exception-test-' . bin2hex(random_bytes(8)) . '.log';
    $_ENV['APP_LOG_PATH'] = $logPath;
    $handler = new ExceptionHandler(debug: false);

    try {
        $handler->report(new HttpException(404, 'Missing'));
        expect(is_file($logPath))->toBeFalse();

        $handler->report(new RuntimeException('Unexpected failure'));
        $handler->report(new HttpException(503, 'Unavailable'));
        $log = file_get_contents($logPath);

        expect($log)->toContain('RuntimeException: Unexpected failure')
            ->and($log)->toContain('ExceptionHandlerTest.php:')
            ->and($log)->toContain('Core\\HttpException: Unavailable');
    } finally {
        if (is_file($logPath)) {
            unlink($logPath);
        }
    }
});
