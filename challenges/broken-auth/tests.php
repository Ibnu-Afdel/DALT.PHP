<?php

/**
 * Broken Authentication Challenge - Test Specification
 * 
 * This file defines the expected behavior after fixing the authentication bug.
 */

return [
    'uses_password_verify' => [
        'type' => 'function_call',
        'file' => 'framework/Core/Authenticator.php',
        'function' => 'password_verify',
        'hint' => 'Use password_verify() to check passwords, not plain text comparison'
    ],

    'not_using_plain_comparison' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Authenticator.php',
        'search' => "\$password == \$user['password']",
        'hint' => 'Remove the == comparison, use password_verify() instead'
    ],

    'not_using_triple_equals' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Authenticator.php',
        'search' => "\$password === \$user['password']",
        'hint' => 'Even === won\'t work with hashed passwords, use password_verify()'
    ]
];
