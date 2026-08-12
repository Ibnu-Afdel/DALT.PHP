<?php

$db = \Core\App::resolve(\Core\Database::class);

$posts = $db->query(
    'SELECT id, title, created_at FROM posts ORDER BY created_at DESC'
)->get();

return ['data' => $posts];
