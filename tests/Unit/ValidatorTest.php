<?php

declare(strict_types=1);

use Core\Validator;

test('string validation accepts only strings and uses inclusive trimmed byte bounds', function () {
    expect(Validator::string(' value ', 5, 5))->toBeTrue()
        ->and(Validator::string(' value ', 6))->toBeFalse()
        ->and(Validator::string('', 0, 0))->toBeTrue()
        ->and(Validator::string('value', 1))->toBeTrue()
        ->and(Validator::string(42))->toBeFalse()
        ->and(Validator::string(true))->toBeFalse()
        ->and(Validator::string(null))->toBeFalse()
        ->and(Validator::string([]))->toBeFalse();
});

test('string validation rejects invalid bound configuration', function (int $min, ?int $max) {
    Validator::string('value', $min, $max);
})->with([
    'negative minimum' => [-1, null],
    'maximum below minimum' => [5, 4],
])->throws(InvalidArgumentException::class);

test('email validation is a strict boolean predicate', function () {
    expect(Validator::email('learner@example.com'))->toBeTrue()
        ->and(Validator::email('not-an-email'))->toBeFalse()
        ->and(Validator::email(' learner@example.com '))->toBeFalse()
        ->and(Validator::email(42))->toBeFalse()
        ->and(Validator::email(null))->toBeFalse()
        ->and(Validator::email([]))->toBeFalse();
});
