<?php

/**
 * Broken Error Handling Challenge — Test Specification
 *
 * Verifies two fixes in framework/Core/ExceptionHandler.php's render():
 *   1. It reads the real status off an HttpException instead of hardcoding 500.
 *   2. It checks $this->debug before disclosing the exception's class, message, and trace.
 *
 * The static checks below can be satisfied by writing the right words back into
 * render() without actually restoring both branches. The handler_result checks
 * dispatch a platform-authored probe controller that constructs ExceptionHandler
 * directly and calls render() on it — the same call public/index.php's outer
 * catch makes — and judge the response it actually produced.
 */

return [
    'http_exception_keeps_its_real_status' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/debug/render-error.php',
        'seed' => ['SELECT 1'],
        'query' => ['kind' => 'http', 'debug' => '0'],
        'expect' => [
            'status' => 404,
            'contains' => '<h1>404</h1><p>Post not found</p>',
        ],
        'hint' => 'render() hardcodes $status = 500. Read it from $exception->statusCode when $exception is an HttpException, exactly like the original: $status = $exception instanceof HttpException ? $exception->statusCode : 500;',
    ],

    'production_hides_the_trace' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/debug/render-error.php',
        'seed' => ['SELECT 1'],
        'query' => ['kind' => 'fatal', 'debug' => '0'],
        'expect' => [
            'status' => 500,
            'contains' => 'Internal Server Error',
            'not_contains' => 'RuntimeException',
        ],
        'hint' => "render() no longer checks \$this->debug before building the detailed page. Add back: if (!\$this->debug) { return \$this->errorResponse(\$status, 'Internal Server Error'); }",
    ],

    'production_hides_the_message' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/debug/render-error.php',
        'seed' => ['SELECT 1'],
        'query' => ['kind' => 'fatal', 'debug' => '0'],
        'expect' => [
            'status' => 500,
            'not_contains' => 'Card charge failed',
        ],
        'hint' => 'The real exception message ("Card charge failed: card ending 4242 declined") must never reach a production response. Only "Internal Server Error" may appear.',
    ],

    'debug_still_shows_the_trace' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/debug/render-error.php',
        'seed' => ['SELECT 1'],
        'query' => ['kind' => 'fatal', 'debug' => '1'],
        'expect' => [
            'status' => 500,
            'contains' => 'RuntimeException',
        ],
        'hint' => 'With debug true, the detailed branch must still run — do not gate it out of existence while fixing the production case.',
    ],

    'reads_the_real_status_code' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/ExceptionHandler.php',
        'search' => '$exception->statusCode',
        'hint' => 'render() must read the real status from an HttpException rather than always using 500.',
    ],

    'checks_debug_before_disclosing' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/ExceptionHandler.php',
        'search' => '$this->debug',
        'hint' => 'render() must inspect $this->debug before choosing the detailed branch.',
    ],
];
