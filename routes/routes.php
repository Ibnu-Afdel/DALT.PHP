<?php

global $router;

$router->get('/', 'welcome.php');
$router->get('/users', 'users/index.php');
$router->get('/users/{id}', 'users/show.php');
$router->get('/db/posts', 'db/posts/index.php');
$router->get('/db/users', 'db/users/index.php');
$router->get('/posts/search', 'posts/search.php');
$router->get('/posts', 'posts/index.php');
$router->post('/posts', 'posts/store.php');
$router->get('/tenant/{tenant_id}/posts', 'tenant/posts.php');
