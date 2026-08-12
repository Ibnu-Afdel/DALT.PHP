<?php

use Core\Response;

$db = \Core\App::resolve(\Core\Database::class);

// Pretend we got the user from the session
$user = ['id' => 1];

$db->query(
    'INSERT INTO posts (title, body, user_id) VALUES (:title, :body, :user_id)',
    [
        'title'   => $_POST['title'] ?? '',
        'body'    => $_POST['body'] ?? '',
        'user_id' => $user['id'],
    ]
);

return Response::json(['success' => true], 201);
