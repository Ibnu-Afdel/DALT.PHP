<?php

$db = \Core\App::resolve(\Core\Database::class);

// BUG 1: The wrong join type is used — every row from the left table should appear
// even when there is no matching row in the right table.
// BUG 2: The join condition links the wrong column — the foreign key is not the primary key.
$posts = $db->query(
    'SELECT posts.id, posts.title, posts.created_at, users.name AS author
     FROM posts
     LEFT JOIN users ON posts.user_id = users.id
     ORDER BY posts.created_at DESC'
)->get();

header('Content-Type: application/json');
echo json_encode($posts);
