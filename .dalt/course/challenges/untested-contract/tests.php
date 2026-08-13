<?php

/**
 * Untested Contract Challenge — Test Specification
 *
 * Verifies one fix in Http/controllers/coupons/redeem.php: a coupon that has
 * already been redeemed must be rejected, not redeemed again.
 *
 * A single manual try — POST once, see { "redeemed": true } — cannot find
 * this bug; it only appears on the second attempt against the same code.
 * These checks seed the database directly in the "already redeemed" state,
 * so the second attempt is proven without needing two real requests.
 */

return [
    'unknown_code_is_rejected' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/coupons/redeem.php',
        'seed' => [
            'CREATE TABLE coupons (code TEXT PRIMARY KEY, times_redeemed INTEGER NOT NULL DEFAULT 0)',
            "INSERT INTO coupons VALUES ('SUMMER10', 0)",
        ],
        'input' => ['code' => 'DOES-NOT-EXIST'],
        'expect' => [
            'status' => 404,
            'contains' => '"redeemed":false',
        ],
        'hint' => 'A coupon code that is not in the table must be rejected with a 404, before anything is updated.',
    ],

    'first_redemption_succeeds' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/coupons/redeem.php',
        'seed' => [
            'CREATE TABLE coupons (code TEXT PRIMARY KEY, times_redeemed INTEGER NOT NULL DEFAULT 0)',
            "INSERT INTO coupons VALUES ('SUMMER10', 0)",
        ],
        'input' => ['code' => 'SUMMER10'],
        'expect' => [
            'status' => 200,
            'contains' => '"redeemed":true',
        ],
        'hint' => 'A coupon seeded as unredeemed (times_redeemed = 0) must still succeed on its first use.',
    ],

    // This is the check that a single manual try cannot reproduce: the
    // database is seeded as if a previous request already redeemed this
    // exact code, and the controller is asked to redeem it again.
    'already_redeemed_coupon_is_rejected' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/coupons/redeem.php',
        'seed' => [
            'CREATE TABLE coupons (code TEXT PRIMARY KEY, times_redeemed INTEGER NOT NULL DEFAULT 0)',
            "INSERT INTO coupons VALUES ('SUMMER10', 1)",
        ],
        'input' => ['code' => 'SUMMER10'],
        'expect' => [
            'status' => 409,
            'contains' => '"redeemed":false',
        ],
        'hint' => "Check \$coupon['times_redeemed'] before the UPDATE. If it is already greater than 0, return Response::json(['redeemed' => false, 'error' => '...'], 409) instead of incrementing it again.",
    ],

    // The response alone is not proof: a fix that returns 409 but still runs
    // the UPDATE first would pass the check above and still corrupt the row.
    // This reads the row the handler actually left behind.
    'already_redeemed_coupon_row_is_unchanged' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/coupons/redeem.php',
        'seed' => [
            'CREATE TABLE coupons (code TEXT PRIMARY KEY, times_redeemed INTEGER NOT NULL DEFAULT 0)',
            "INSERT INTO coupons VALUES ('SUMMER10', 1)",
        ],
        'input' => ['code' => 'SUMMER10'],
        'inspect' => "SELECT times_redeemed FROM coupons WHERE code = 'SUMMER10'",
        'expect' => [
            'source' => 'inspect',
            'contains' => '"times_redeemed":1',
        ],
        'hint' => 'A rejected second attempt must leave times_redeemed at 1, not increment it to 2. Reject before the UPDATE runs, not just in the response.',
    ],
];
