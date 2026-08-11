<?php

$db = \Core\App::resolve(\Core\Database::class);

$id = $_GET['id'] ?? null;

// BUG 2: Wrong column name — the users table has a column called "id", not "user_id".
// This query will always return no results (or throw an error on strict DBs).
$user = $db->query(
    'SELECT id, name, email, created_at FROM users WHERE id = :id',
    ['id' => $id]
)->find();

if (!$user) {
    http_response_code(404);
    // BUG 3: var_dump is a debug tool, not a JSON response.
    // The Content-Type header says JSON but the body is PHP var_dump output.
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not found']);
    exit;
}

header('Content-Type: application/json');
// BUG 3 (continued): the success path is also missing json_encode.
echo json_encode($user);
