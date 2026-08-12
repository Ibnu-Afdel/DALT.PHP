<?php

$db = \Core\App::resolve(\Core\Database::class);

$search = $_GET['search'] ?? '';

if ($search) {
    $users = $db->query(
        "SELECT id, name, email, created_at FROM users WHERE email LIKE '%{$search}%' ORDER BY created_at DESC"
    )->get();
} else {
    $users = $db->query(
        'SELECT id, name, email, created_at FROM users ORDER BY created_at DESC'
    )->get();
}

return $users;
