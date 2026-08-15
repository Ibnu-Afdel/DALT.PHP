<?php

declare(strict_types=1);

use FsLab\IssueApi;

/**
 * Worked examples for FS06.1.
 *
 * Every test here observes two things separately: what a client receives, and what the
 * database holds afterwards. Neither one alone is enough. A 201 does not prove a row
 * exists, and a row does not prove the public contract is right.
 *
 * Run them:
 *   php vendor/bin/pest .dalt/course/fullstack/api-behavior-tests-lab/tests \
 *     --bootstrap=.dalt/course/fullstack/api-behavior-tests-lab/bootstrap.php
 */

beforeEach(function () {
    // A fresh in-memory database per test. This is the strongest isolation available:
    // no cleanup to forget, no order dependency, no leftover row from yesterday.
    $this->api = IssueApi::withSchema();
    $this->projectId = $this->api->seedProject('Website');
});

function countRows(PDO $pdo, string $table): int
{
    return (int) $pdo->query("SELECT count(*) AS c FROM {$table}")->fetch()['c'];
}

test('a valid issue is created, returned, and stored', function () {
    $response = $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => 'Write API tests',
        'priority' => 'high',
    ]);

    // 1. The response a client can use.
    expect($response['status'])->toBe(201);
    expect($response['body']['data']['title'])->toBe('Write API tests');
    expect($response['body']['data']['status'])->toBe('todo');   // the schema default
    expect($response['body']['data']['priority'])->toBe('high');

    // 2. The durable effect a later request would see. This is the assertion that
    //    catches a handler returning 201 without writing anything.
    expect(countRows($this->api->pdo(), 'issues'))->toBe(1);

    // 3. Both writes of the business fact, not just the interesting one.
    expect(countRows($this->api->pdo(), 'activity'))->toBe(1);
});

test('the response exposes only the agreed fields', function () {
    $response = $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => 'Check the envelope',
    ]);

    // Pinning the key set is how a response stops leaking a column somebody adds later.
    expect(array_keys($response['body']['data']))
        ->toBe(['id', 'projectId', 'title', 'status', 'priority']);
});

test('a blank title is refused with 422 and writes nothing', function () {
    $response = $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => '   ',
    ]);

    // Not merely "an error": the exact status and the documented shape. A test that
    // accepted any non-200 would also pass on a 500 from a misspelled column.
    expect($response['status'])->toBe(422);
    expect($response['body']['error']['code'])->toBe('validation_failed');
    expect($response['body']['error']['fields']['title'])->toBe('Required');

    expect(countRows($this->api->pdo(), 'issues'))->toBe(0);
});

test('every invalid field is reported, not just the first', function () {
    $response = $this->api->handle('POST', '/api/issues', [
        'title' => '',
        'priority' => 'urgent',
    ]);

    expect($response['status'])->toBe(422);
    expect(array_keys($response['body']['error']['fields']))
        ->toBe(['title', 'project_id', 'priority']);
});

test('an unaccepted field cannot reach the database', function () {
    // The allowlist regression test. If someone later iterates the request body
    // instead of naming fields, this fails.
    $response = $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => 'Smuggling attempt',
        'status' => 'done',            // not accepted on create
    ]);

    expect($response['status'])->toBe(201);
    expect($response['body']['data']['status'])->toBe('todo');
});

test('creating an issue in a project that does not exist is 404', function () {
    $response = $this->api->handle('POST', '/api/issues', [
        'project_id' => 99999,
        'title' => 'Orphan',
    ]);

    expect($response['status'])->toBe(404);
    expect($response['body']['error']['code'])->toBe('not_found');
    expect(countRows($this->api->pdo(), 'issues'))->toBe(0);
});

test('a missing issue is 404, not an empty 200', function () {
    $response = $this->api->handle('GET', '/api/issues/4242');

    expect($response['status'])->toBe(404);
    expect($response['body']['error']['code'])->toBe('not_found');
});

test('deleting an issue returns 204 with no body and removes the row', function () {
    $created = $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => 'Temporary',
    ]);
    $id = $created['body']['data']['id'];

    $response = $this->api->handle('DELETE', "/api/issues/{$id}");

    expect($response['status'])->toBe(204);
    expect($response['body'])->toBeNull();
    expect(countRows($this->api->pdo(), 'issues'))->toBe(0);

    // Deleting twice is 404 the second time: the client learns the difference between
    // "I deleted it" and "there was nothing there".
    expect($this->api->handle('DELETE', "/api/issues/{$id}")['status'])->toBe(404);
});

test('a failed second write rolls back the first', function () {
    // The activity table caps its message at 40 characters. A long title therefore
    // succeeds at the issue insert and fails at the activity insert — a real failure
    // in the second write of a two-write operation, provoked without editing the code
    // under test.
    $longTitle = str_repeat('long ', 12);   // 60 characters

    expect(fn () => $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => $longTitle,
    ]))->toThrow(PDOException::class);

    // Neither row exists. This is the assertion that distinguishes a rollback from a
    // caught exception: catching leaves the first write committed, rollback does not.
    expect(countRows($this->api->pdo(), 'issues'))->toBe(0);
    expect(countRows($this->api->pdo(), 'activity'))->toBe(0);
});

test('tests do not depend on each other for ids', function () {
    // Each test gets a fresh database, so this issue is id 1 — and the previous eight
    // tests cannot change that. If this ever fails, isolation has broken somewhere.
    $created = $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => 'First in a clean database',
    ]);

    expect($created['body']['data']['id'])->toBe('1');
});
