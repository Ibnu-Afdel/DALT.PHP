<?php

/**
 * Static checks for the two middleware defects in this challenge.
 *
 * Both fixtures implement MiddlewareInterface, so the seeded application runs
 * and each defect is observable through /dashboard and /dashboard/update.
 * Runtime assertions are pending the challenge-verifier work (DALT-0062).
 */

return [
    // Executable contract checks. These load the class in a separate process,
    // so a fixture that does not implement the interface — or does not parse —
    // fails here instead of passing a source match and blowing up at runtime.
    'auth_implements_the_middleware_contract' => [
        'type' => 'class_contract',
        'file' => 'framework/Core/Middleware/Auth.php',
        'class' => 'Core\Middleware\Auth',
        'implements' => ['Core\Middleware\MiddlewareInterface'],
        'methods' => ['handle'],
        'hint' => 'Auth must implement Core\Middleware\MiddlewareInterface. The pipeline rejects anything else before it ever runs.',
    ],

    'csrf_implements_the_middleware_contract' => [
        'type' => 'class_contract',
        'file' => 'framework/Core/Middleware/Csrf.php',
        'class' => 'Core\Middleware\Csrf',
        'implements' => ['Core\Middleware\MiddlewareInterface'],
        'methods' => ['handle'],
        'hint' => 'Csrf must implement Core\Middleware\MiddlewareInterface, with handle(Request, Closure): Response.',
    ],

    // Note: 'function_call' matches global functions only — it skips any name
    // preceded by -> or ::, so a method call needs a source check instead.
    'auth_asks_the_authenticator' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Middleware/Auth.php',
        'search' => '$this->auth->guest()',
        'hint' => 'Auth should ask Core\Authenticator whether the visitor is a guest, not read a session key directly.',
    ],

    'auth_does_not_read_a_stale_session_key' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Middleware/Auth.php',
        'search' => "\$_SESSION['authenticated']",
        'hint' => 'Nothing in the login flow ever writes this key, so the check can never see a real user.',
    ],

    'csrf_uses_timing_safe_comparison' => [
        'type' => 'function_call',
        'file' => 'framework/Core/Middleware/Csrf.php',
        'function' => 'hash_equals',
        'hint' => 'Compare tokens with hash_equals() so response time does not reveal how much of the token matched.',
    ],

    'csrf_rejects_when_tokens_differ' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Middleware/Csrf.php',
        'search' => '!hash_equals($sessionToken, $requestToken)',
        'hint' => 'The 419 response belongs on the failure path: reject when the tokens do NOT match.',
    ],

    'csrf_does_not_reject_on_match' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Middleware/Csrf.php',
        'search' => 'if ($sessionToken == $requestToken) {',
        'hint' => 'This condition is inverted, and loose comparison also treats a missing token as a pass.',
    ],

    'csrf_still_guards_a_missing_token' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Middleware/Csrf.php',
        'search' => "\$requestToken === ''",
        'hint' => 'An absent or empty request token must fail before the comparison runs.',
    ],
];
