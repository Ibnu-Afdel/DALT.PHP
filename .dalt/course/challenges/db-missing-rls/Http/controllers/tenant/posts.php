<?php

$db = \Core\App::resolve(\Core\Database::class);
$request = \Core\App::resolve(\Core\Request::class);

$tenantId = (int) $request->route('tenant_id');

$posts = $db->query(
    'SELECT * FROM posts WHERE tenant_id = :id ORDER BY created_at DESC',
    ['id' => $tenantId]
)->get();

return ['data' => $posts];
