<?php

/**
 * Broken Session Challenge — Test Specification
 *
 * Verifies two fixes in framework/Core/Session.php:
 *   1. Session::get() reads flash data before persistent data.
 *   2. Session::ageFlashData() drops the previous 'old' bag instead of
 *      carrying it forward, so a flash value expires after one more request.
 *
 * The static checks below can be satisfied by writing the right words into
 * Session.php in the wrong order or as dead code. The handler_result checks
 * run a real controller — which loads this challenge's own Session.php — and
 * judge what it actually produced: the rendered page, or the session state
 * left behind. Session data never appears in the response body on its own,
 * which is why two of these read 'source' => 'session' instead.
 */

return [
    'flash_wins_over_persistent_value' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/contact/precedence.php',
        'seed' => ['SELECT 1'],
        'expect' => [
            'status' => 200,
            'contains' => '<p id="probe-value">flash value</p>',
        ],
        'hint' => "The probe route puts a persistent value and flashes a different one under the same key, then reads it back. Session::get() must return the flash value, not the persistent one.",
    ],

    'flash_written_last_request_is_readable_now' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/contact/success.php',
        'seed' => ['SELECT 1'],
        // Simulates a flash value written during the previous request (still
        // in the 'new' bag) and stale data from the request before that
        // (already in 'old', which must expire on this boundary).
        'session' => [
            '_flash' => [
                'new' => ['success' => 'Message sent successfully!'],
                'old' => ['stale' => 'must not survive another request'],
            ],
        ],
        'expect' => [
            'status' => 200,
            'contains' => 'Message sent successfully!',
        ],
        'hint' => 'Flash data written on the previous request becomes readable on this one. ageFlashData() must promote the previous new bag into old.',
    ],

    'stale_flash_data_does_not_survive_another_request' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/contact/success.php',
        'seed' => ['SELECT 1'],
        'session' => [
            '_flash' => [
                'new' => ['success' => 'Message sent successfully!'],
                'old' => ['stale' => 'must not survive another request'],
            ],
        ],
        'expect' => [
            'source' => 'session',
            'not_contains' => 'must not survive another request',
        ],
        'hint' => "ageFlashData() must replace the old bag with the previous request's new bag — not append to it. Carrying the previous old bag forward means a flash value never expires.",
    ],

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
