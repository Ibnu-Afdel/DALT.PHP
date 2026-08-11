<?php

$db = \Core\App::resolve(\Core\Database::class);

$tenantId = (int)$router->getParam('tenant_id');

$db->query(
    'SET app.tenant_id = :id',
    ['id' => $tenantId]
);

$posts = $db->query(
    'SELECT * FROM posts ORDER BY created_at DESC'
)->get();

header('Content-Type: application/json');
echo json_encode(['data' => $posts]);
