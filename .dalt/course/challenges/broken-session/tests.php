<?php

/**
 * Static checks for the two session-lifecycle defects in this challenge.
 * Runtime behavior is demonstrated by the contact and precedence routes.
 */

return [
    'flash_value_is_read_before_fallback' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Session.php',
        'search' => "if (\$flash['found']) {",
        'hint' => 'Session::get() should inspect the flash lookup before persistent session data.',
    ],

    'flash_value_is_returned' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Session.php',
        'search' => 'return $flash[\'value\'];',
        'hint' => 'Return the found flash value before falling back to the persistent session value.',
    ],

    'persistent_value_does_not_win' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Session.php',
        'search' => 'return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : self::getFlash($key, $default);',
        'hint' => 'The persistent session lookup is currently ahead of the flash lookup.',
    ],

    'aging_drops_previous_old_values' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Session.php',
        'search' => "'old' => [...\$legacy, ...\$new],",
        'hint' => 'At request start, replace old data with the previous new data; do not carry old data forward.',
    ],

    'aging_does_not_carry_old_values' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Session.php',
        'search' => "'old' => [...\$legacy, ...\$old, ...\$new],",
        'hint' => 'Previously old flash data must expire before the new request runs.',
    ],

    'session_start_ages_flash_data' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Session.php',
        'search' => 'self::ageFlashData();',
        'hint' => 'Flash aging belongs at the request-start session boundary.',
    ],
];
