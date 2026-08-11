<?php

$db = \Core\App::resolve(\Core\Database::class);

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query parameter q is required']);
    exit;
}

$posts = $db->query(
    "SELECT id, title, created_at,
            ts_rank(search_vector, plainto_tsquery('english', :q)) AS relevance
     FROM posts
     WHERE search_vector @@ plainto_tsquery('english', :q)
     ORDER BY relevance DESC
     LIMIT 20",
    ['q' => $q]
)->get();

header('Content-Type: application/json');
echo json_encode(['query' => $q, 'results' => $posts]);
