<?php

$db = \Core\App::resolve(\Core\Database::class);

// Pretend we got the user from the session
$user = ['id' => 1];

// BUG: The metadata column is missing from the INSERT statement
// BUG: The :metadata parameter is not passed
$db->query(
    'INSERT INTO posts (title, body, user_id, metadata) VALUES (:title, :body, :user_id, :metadata)',
    [
        'title'   => $_POST['title'] ?? '',
        'body'    => $_POST['body'] ?? '',
        'user_id' => $user['id'],
        'metadata' => $_POST['metadata'] ?? null,
    ]
);

header('Content-Type: application/json');
http_response_code(201);
echo json_encode(['success' => true]);
