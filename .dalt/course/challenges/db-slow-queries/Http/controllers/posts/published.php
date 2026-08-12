<?php

$db = \Core\App::resolve(\Core\Database::class);

return ['data' => $db->query(
    'SELECT * FROM posts WHERE status = :status ORDER BY created_at DESC LIMIT 50',
    ['status' => 'published']
)->get()];
