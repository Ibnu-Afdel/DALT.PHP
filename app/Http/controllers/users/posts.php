<?php

$db = \Core\App::resolve(\Core\Database::class);

$userId = (int)$router->getParam('id');

// This query is slow because there is no index on user_id
$posts = $db->query(
    'SELECT * FROM posts WHERE user_id = :id ORDER BY created_at DESC',
    ['id' => $userId]
)->get();

header('Content-Type: application/json');
echo json_encode(['data' => $posts]);
