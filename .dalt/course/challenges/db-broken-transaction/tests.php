<?php

/**
 * Broken Transaction Challenge — Test Specification
 *
 * Verifies that the transfer endpoint wraps its UPDATEs in a try/catch that
 * rolls back and returns a controlled response on failure.
 */

return [
    // Executable checks. The static checks below can be satisfied by writing
    // the right words into a catch block that never actually undoes anything —
    // e.g. catching the error but still reaching an unconditional commit() in a
    // finally block. These run the controller against a seeded database and a
    // transfer that is forced to fail on its second UPDATE (a CHECK constraint
    // standing in for the FK/constraint failure Postgres would raise), then
    // judge what actually happened: the response, and the row left behind.
    'failing_transfer_returns_a_controlled_response' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/db/transfer.php',
        'seed' => [
            'CREATE TABLE users (id INTEGER PRIMARY KEY, credits INTEGER NOT NULL CHECK (credits <= 100))',
            'INSERT INTO users VALUES (1, 50)',
            'INSERT INTO users VALUES (2, 80)',
        ],
        'input' => ['from_id' => '1', 'to_id' => '2', 'amount' => '30'],
        'expect' => [
            'status' => 422,
            'contains' => '"success":false',
        ],
        'hint' => 'The second UPDATE fails (it would push user 2 over the credits limit). An uncaught exception is not a controlled response — catch it and return Response::json([...], 422).',
    ],

    'failing_transfer_leaves_no_partial_write' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/db/transfer.php',
        'seed' => [
            'CREATE TABLE users (id INTEGER PRIMARY KEY, credits INTEGER NOT NULL CHECK (credits <= 100))',
            'INSERT INTO users VALUES (1, 50)',
            'INSERT INTO users VALUES (2, 80)',
        ],
        'input' => ['from_id' => '1', 'to_id' => '2', 'amount' => '30'],
        'inspect' => 'SELECT credits FROM users WHERE id = 1',
        'expect' => [
            'source' => 'inspect',
            'contains' => '"credits":50',
        ],
        'hint' => 'The first UPDATE succeeded before the second one failed. Catching the exception is not enough — without an explicit rollBack() (or with one that never runs because it sits after an unconditional commit()), user 1 keeps the debit permanently even though user 2 never received it.',
    ],

    'uses_begin_transaction' => [
        'type'   => 'file_contains',
        'file'   => 'Http/controllers/db/transfer.php',
        'search' => 'beginTransaction',
        'hint'   => 'Keep the $pdo->beginTransaction() call — it must come before the first UPDATE',
    ],

    'uses_commit' => [
        'type'   => 'file_contains',
        'file'   => 'Http/controllers/db/transfer.php',
        'search' => 'commit',
        'hint'   => 'Keep the $pdo->commit() call — it applies both UPDATEs atomically on success',
    ],

    'uses_rollback' => [
        'type'   => 'file_contains',
        'file'   => 'Http/controllers/db/transfer.php',
        'search' => 'rollBack',
        'hint'   => 'Add $pdo->rollBack() in the catch block — without it a failed second UPDATE leaves the first committed permanently',
    ],

    'uses_catch' => [
        'type'   => 'file_contains',
        'file'   => 'Http/controllers/db/transfer.php',
        'search' => 'catch',
        'hint'   => 'Wrap both UPDATEs in a try { ... } catch (\Exception $e) { $pdo->rollBack(); } block',
    ],
];
