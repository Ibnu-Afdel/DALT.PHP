<?php

declare(strict_types=1);

// A deliberately small, resettable API for Part 04. Run with:
// php -S 127.0.0.1:8034 fixture-api.php
// State belongs to this PHP process only; restart it to restore the seed.

header('Content-Type: application/json; charset=utf-8');

// Reflect a local dev origin instead of hardcoding one. The Part 03 lab serves on
// :5174 and this fixture used to allow only :5173, so the learner's first fetch of
// Part 04 failed CORS and B04 told them to go and edit this header — debugging the
// course instead of learning the lesson.
//
// Reflected, not `*`, and deliberately: `*` is incompatible with credentialed
// requests, so it would quietly teach a shape that breaks in Part 06 when cookies
// arrive. The allowlist is loopback-only because this fixture is a teaching prop.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('#^http://(localhost|127\.0\.0\.1)(:[0-9]+)?$#', $origin) === 1) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
// Any cache between browser and fixture must key on Origin, or one origin's
// permission gets replayed to another.
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function respond(int $status, mixed $body): never
{
    http_response_code($status);

    // 204 means "no content" literally: RFC 9110 6.4.1 forbids a body, and Part 04
    // teaches the learner to branch before parsing rather than call .json() on it.
    // Echoing json_encode(null) here would put four bytes on the wire after the
    // status line — invisible to curl, visible to any client that reads to close,
    // and a direct contradiction of the lesson this fixture exists to demonstrate.
    if ($status === 204) {
        header_remove('Content-Type');
        exit;
    }

    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit;
}

function notFound(): never
{
    respond(404, ['error' => ['code' => 'not_found', 'message' => 'issue not found']]);
}

// A CORS preflight. FS04.2 tells the learner to expect two Network rows per
// mutation; routing it through respond() keeps the 204 genuinely bodiless and
// header-clean rather than shipping a Content-Type for content that is not there.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    respond(204, null);
}

function methodNotAllowed(string $allowed): never
{
    // 405 owes the client an Allow header (RFC 9110 15.5.6). FS05.1 tells the learner
    // status and headers are part of the contract, so the fixture honours it.
    header('Allow: ' . $allowed);
    respond(405, ['error' => ['code' => 'method_not_allowed', 'message' => 'allowed: ' . $allowed]]);
}

function issues(): array
{
    // `projectId` is here because B02 typed `Project` as a domain entity and put
    // `projectId` on CreateIssueInput, and FS05.2 makes projects a real table. A
    // fixture without it would drop the field out of the domain for one whole part
    // and bring it back in the next, so the learner's own Issue type would stop
    // matching the server through no fault of theirs.
    static $issues = [
        ['id' => 'ISS-41', 'projectId' => 'PRJ-1', 'title' => 'Show a loading state', 'status' => 'todo', 'priority' => 'high'],
        ['id' => 'ISS-42', 'projectId' => 'PRJ-1', 'title' => 'Read the response body', 'status' => 'in_progress', 'priority' => 'medium'],
        ['id' => 'ISS-43', 'projectId' => 'PRJ-1', 'title' => 'Keep server truth visible', 'status' => 'done', 'priority' => 'low'],
    ];

    return $issues;
}

// The built-in server includes this router for every request, so a process-local
// static does not survive requests. Persist only under the system temp directory.
$state = sys_get_temp_dir() . '/dalt-fs04-fixture-' . getmypid() . '.json';
if (!is_file($state)) {
    file_put_contents($state, json_encode(issues(), JSON_THROW_ON_ERROR));
}
$all = json_decode((string) file_get_contents($state), true, flags: JSON_THROW_ON_ERROR);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

if ($path === '/api/issues' && $method === 'GET') {
    respond(200, $all);
}

if ($path === '/api/issues' && $method === 'POST') {
    $input = json_decode((string) file_get_contents('php://input'), true);
    $title = is_array($input) ? trim((string) ($input['title'] ?? '')) : '';
    if ($title === '') {
        respond(422, ['error' => ['code' => 'validation_failed', 'message' => 'title is required']]);
    }
    // FS03.3 has the learner build a priority select and verify that "the new issue
    // appears with the chosen priority", and types the draft as `{title, priority}`
    // precisely because id and status are *not* the user's to supply. A fixture that
    // ignored priority would delete that feature the moment B04 Stage 2 pointed the
    // form at the server, and the learner would go looking for the bug in their own
    // request body. Same discontinuity as the missing projectId, one field over.
    $priority = is_array($input) ? ($input['priority'] ?? 'medium') : 'medium';
    if (!in_array($priority, ['low', 'medium', 'high'], true)) {
        respond(422, ['error' => ['code' => 'validation_failed', 'message' => 'priority is invalid']]);
    }
    // projectId is server-assigned here, not read from the body. Part 04 has one
    // project and no way to choose; FS06.3 is where deriving a field on the server
    // rather than trusting the client becomes the actual subject.
    $issue = [
        'id' => 'ISS-' . (44 + count($all)),
        'projectId' => 'PRJ-1',
        'title' => $title,
        'status' => 'todo',
        'priority' => $priority,
    ];
    $all[] = $issue;
    file_put_contents($state, json_encode($all, JSON_THROW_ON_ERROR));
    respond(201, $issue);
}

// Match any single-segment ID, not only well-formed ones. Scoping the pattern to
// `ISS-[0-9]+` sent /api/issues/999 down to the 405 catch-all, which contradicts
// FS05.1: an addressable resource that does not exist is 404, whatever its ID
// looks like. 405 is reserved for a real resource asked to do the wrong thing.
if (preg_match('#^/api/issues/([^/]+)$#', (string) $path, $match) === 1) {
    $index = array_search(rawurldecode($match[1]), array_column($all, 'id'), true);
    if ($index === false) {
        notFound();
    }
    if ($method === 'GET') {
        respond(200, $all[$index]);
    }
    if ($method === 'PATCH') {
        $input = json_decode((string) file_get_contents('php://input'), true);
        $status = is_array($input) ? ($input['status'] ?? null) : null;
        if (!in_array($status, ['todo', 'in_progress', 'done'], true)) {
            respond(422, ['error' => ['code' => 'validation_failed', 'message' => 'status is invalid']]);
        }
        $all[$index]['status'] = $status;
        file_put_contents($state, json_encode($all, JSON_THROW_ON_ERROR));
        respond(200, $all[$index]);
    }
    if ($method === 'DELETE') {
        array_splice($all, $index, 1);
        file_put_contents($state, json_encode($all, JSON_THROW_ON_ERROR));
        respond(204, null);
    }

    methodNotAllowed('GET, PATCH, DELETE');
}

if ($path === '/api/issues') {
    methodNotAllowed('GET, POST');
}

// An unrouted path is missing, not wrongly-addressed. Returning 405 here told the
// learner a typo was a method problem.
notFound();
