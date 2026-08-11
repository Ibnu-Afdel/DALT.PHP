<?php

global $router;

$router->get('/', 'welcome.php');
$router->get('/users', 'users/index.php');
$router->get('/users/{id}', 'users/show.php');
