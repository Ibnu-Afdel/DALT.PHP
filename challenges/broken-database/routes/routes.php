<?php

global $router;

$router->get('/', 'welcome.php');

// Posts routes
$router->get('/posts', 'posts/index.php');
$router->get('/posts/{id}', 'posts/show.php');
