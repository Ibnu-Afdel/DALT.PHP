<?php

/**
 * Broken Session Challenge - Test Specification
 * 
 * This file defines the expected behavior after fixing the session bugs.
 */

return [
    'flash_checked_first' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Session.php',
        'search' => "\$_SESSION['_flash'][\$key] ?? \$_SESSION[\$key]",
        'hint' => 'Check flash data BEFORE regular session data in Session::get()'
    ],

    'not_checking_regular_first' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Session.php',
        'search' => "\$_SESSION[\$key] ?? \$_SESSION['_flash'][\$key]",
        'hint' => 'Wrong order! Flash should be checked first, not regular session'
    ],

    'unflash_enabled' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Session.php',
        'search' => "unset(\$_SESSION['_flash'])",
        'hint' => 'Uncomment the unset() line in Session::unflash()'
    ],

    'unflash_not_commented' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Session.php',
        'search' => "// unset(\$_SESSION['_flash'])",
        'hint' => 'Remove the // comment from unset() in Session::unflash()'
    ]
];
