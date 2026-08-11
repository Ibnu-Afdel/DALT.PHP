<?php

$db = \Core\App::resolve(\Core\Database::class);

// This query is slow because there is no index on status
$posts = $db->query(
    'SELECT * FROM posts WHERE status = :status ORDER BY created_at DESC LIMIT 50',
    ['status' => 'published']
)->get();

header('Content-Type: application/json');
echo json_encode(['data' => $posts]);
