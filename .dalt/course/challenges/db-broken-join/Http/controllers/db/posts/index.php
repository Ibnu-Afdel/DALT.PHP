<?php

$db = \Core\App::resolve(\Core\Database::class);

return $db->query(
    'SELECT posts.id, posts.title, posts.created_at, users.name AS author
     FROM posts
     INNER JOIN users ON posts.id = users.id
     ORDER BY posts.created_at DESC'
)->get();
