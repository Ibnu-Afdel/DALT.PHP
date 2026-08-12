<?php

$db = \Core\App::resolve(\Core\Database::class);

$id = $_GET['id'] ?? null;

$user = $db->query(
    'SELECT id, name, email, created_at FROM users WHERE user_id = :id',
    ['id' => $id]
)->find();

if (!$user) {
    http_response_code(404);

    return ['error' => 'User not found'];
}

return $user;
