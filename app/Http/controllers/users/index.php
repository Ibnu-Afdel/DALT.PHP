<?php

$db = \Core\App::resolve(\Core\Database::class);

$search = $_GET['search'] ?? '';

// BUG 1: SQL injection — user input is interpolated directly into the query string.
// An attacker can submit ?search='; DROP TABLE users;-- to destroy your data.
if ($search) {
    $users = $db->query(
        'SELECT id, name, email, created_at FROM users WHERE email LIKE :search ORDER BY created_at DESC',
        ['search' => '%' . $search . '%']
    )->get();
} else {
    $users = $db->query(
        'SELECT id, name, email, created_at FROM users ORDER BY created_at DESC'
    )->get();
}

header('Content-Type: application/json');
echo json_encode($users);
