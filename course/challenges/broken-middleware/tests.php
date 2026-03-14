<?php

/**
 * Broken Middleware Challenge - Test Specification
 * 
 * This file defines the expected behavior after fixing the middleware bugs.
 */

return [
    'auth_checks_user_key' => [
        'type' => 'session_key',
        'file' => 'framework/Core/Middleware/Auth.php',
        'key' => 'user',
        'hint' => "Change \$_SESSION['authenticated'] to \$_SESSION['user'] in Auth middleware"
    ],

    'auth_not_checking_authenticated' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Middleware/Auth.php',
        'search' => "['authenticated']",
        'hint' => 'Remove references to authenticated key, use user key instead'
    ],

    'csrf_uses_hash_equals' => [
        'type' => 'function_call',
        'file' => 'framework/Core/Middleware/Csrf.php',
        'function' => 'hash_equals',
        'hint' => 'Use hash_equals() for timing-safe token comparison'
    ],

    'csrf_logic_correct' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Middleware/Csrf.php',
        'search' => '!hash_equals',
        'hint' => 'CSRF should reject when tokens DON\'T match (use ! or NOT logic)'
    ]
];
