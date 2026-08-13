<?php

/**
 * Platform-authored probe controller. It exists so the verifier can exercise
 * ExceptionHandler::render() directly through the same handler_result path
 * every other executable check uses — the defect is in ExceptionHandler
 * itself, not in this file, which you should not need to change.
 *
 * ?kind=http    -> HttpException(404, ...), status must survive untouched
 * ?kind=fatal   -> a plain RuntimeException, status is always 500
 * &debug=1      -> ExceptionHandler is constructed with debug: true
 */

use Core\ExceptionHandler;
use Core\HttpException;

$debug = ($_GET['debug'] ?? '0') === '1';
$kind = $_GET['kind'] ?? 'http';

$exception = $kind === 'http'
    ? new HttpException(404, 'Post not found')
    : new RuntimeException('Card charge failed: card ending 4242 declined');

$handler = new ExceptionHandler($debug);

return $handler->render($exception);
