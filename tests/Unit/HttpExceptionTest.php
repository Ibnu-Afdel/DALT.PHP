<?php

declare(strict_types=1);

use Core\HttpException;

test('http exceptions expose an immutable error status and useful message', function () {
    $exception = new HttpException(404, 'Missing article');

    expect($exception->statusCode)->toBe(404)
        ->and($exception->getCode())->toBe(404)
        ->and($exception->getMessage())->toBe('Missing article');
});

test('http exceptions reject statuses outside the error range', function (int $status) {
    new HttpException($status);
})->with([399, 600])->throws(InvalidArgumentException::class);

test('abort throws without mutating the response status first', function () {
    http_response_code(201);

    try {
        abort(404);
    } catch (HttpException $exception) {
        expect($exception->statusCode)->toBe(404)
            ->and($exception->getMessage())->toBe('Not Found')
            ->and(http_response_code())->toBe(201);

        return;
    }

    test()->fail('abort() did not throw.');
});

test('authorize returns for an allowed decision and aborts a denied decision', function () {
    expect(authorize(true))->toBeNull();

    authorize(false, 403, 'Private area');
})->throws(HttpException::class, 'Private area');
