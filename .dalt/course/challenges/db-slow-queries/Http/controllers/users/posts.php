<?php

$db = \Core\App::resolve(\Core\Database::class);
$request = \Core\App::resolve(\Core\Request::class);

return ['data' => $db->query(
    'SELECT * FROM posts WHERE user_id = :id ORDER BY created_at DESC',
    ['id' => (int) $request->route('id')]
)->get()];
