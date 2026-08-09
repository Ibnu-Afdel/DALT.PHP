<?php

declare(strict_types=1);

use Core\ValidationException;

test('validation exceptions initialize their error bag and old input', function () {
    $exception = new ValidationException(
        ['email' => 'A valid email is required.'],
        ['email' => 'invalid', 'nickname' => null],
    );

    expect($exception->getMessage())->toBe('The given data was invalid.')
        ->and($exception->errors)->toBe(['email' => 'A valid email is required.'])
        ->and($exception->old)->toBe(['email' => 'invalid', 'nickname' => null]);
});

test('the validation throw helper throws the initialized exception', function () {
    try {
        ValidationException::throw(['name' => 'Name is required.'], ['name' => '']);
    } catch (ValidationException $exception) {
        expect($exception->errors)->toBe(['name' => 'Name is required.'])
            ->and($exception->old)->toBe(['name' => '']);

        return;
    }

    test()->fail('ValidationException::throw() did not throw.');
});

test('validation exceptions reject invalid error bags', function (array $errors) {
    new ValidationException($errors);
})->with([
    'empty bag' => [[]],
    'numeric field' => [[0 => 'Invalid']],
    'empty field' => [['' => 'Invalid']],
    'non-string message' => [['email' => ['Invalid']]],
    'empty message' => [['email' => '']],
])->throws(InvalidArgumentException::class);

test('validation exceptions reject numeric old-input keys', function () {
    new ValidationException(['name' => 'Invalid'], [0 => 'value']);
})->throws(InvalidArgumentException::class, 'Old input keys must be strings.');
