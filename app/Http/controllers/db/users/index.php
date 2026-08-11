<?php

$db = \Core\App::resolve(\Core\Database::class);

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

$users = $db->query(
    'SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
    ['limit' => $limit, 'offset' => $offset]
)->get();

header('Content-Type: application/json');
echo json_encode([
    'data' => $users,
    'page' => $page,
    'limit' => $limit,
]);
