<?php

use Core\Response;

$db = \Core\App::resolve(\Core\Database::class);

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    return Response::json(['error' => 'Query parameter q is required'], 400);
}

$posts = $db->query(
    'SELECT id, title, created_at FROM posts WHERE title ILIKE :q ORDER BY created_at DESC',
    ['q' => '%' . $q . '%']
)->get();

return ['query' => $q, 'results' => $posts];
