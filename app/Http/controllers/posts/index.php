<?php

$db = \Core\App::resolve(\Core\Database::class);

// BUG: The metadata column is missing from the SELECT statement
$posts = $db->query(
    'SELECT id, title, metadata, created_at FROM posts ORDER BY created_at DESC'
)->get();

header('Content-Type: application/json');
echo json_encode(['data' => $posts]);
