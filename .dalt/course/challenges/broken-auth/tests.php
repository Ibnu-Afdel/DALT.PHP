<?php

/**
 * Static checks for the single credential-verification defect in this challenge.
 *
 * The fixture is the real Authenticator with one line changed, so every other
 * part of the auth flow — the guards, the intended redirect, identity
 * validation — keeps working and the defect is observable at the login form.
 * Runtime assertions are pending the challenge-verifier work (DALT-0062).
 */

return [
    // Executable contract check. The Auth and Guest middleware and the login
    // stub all call into Authenticator; a fixture that drops those methods
    // makes every guarded route fatal, which no source match would catch.
    'authenticator_keeps_its_public_surface' => [
        'type' => 'class_contract',
        'file' => 'framework/Core/Authenticator.php',
        'class' => 'Core\Authenticator',
        'methods' => ['attempt', 'login', 'logout', 'user', 'id', 'check', 'guest', 'rememberIntended', 'intended'],
        'hint' => 'Keep the whole Authenticator API. Auth and Guest middleware call check()/guest(), and the login controller calls intended().',
    ],

    'uses_password_verify' => [
        'type' => 'function_call',
        'file' => 'framework/Core/Authenticator.php',
        'function' => 'password_verify',
        'hint' => 'A stored hash cannot be compared to a submitted password directly; hash the candidate the same way and compare the results.',
    ],

    'no_loose_comparison_against_the_hash' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Authenticator.php',
        'search' => '$password == $hash',
        'hint' => 'Loose comparison of a plain password against a stored hash can never succeed for a correct password.',
    ],

    'no_strict_comparison_against_the_hash' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Authenticator.php',
        'search' => '$password === $hash',
        'hint' => 'Tightening the comparison does not help: the two values are different kinds of string.',
    ],
];
