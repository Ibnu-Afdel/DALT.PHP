<?php

global $router;

$router->get('/', 'welcome.php');

// Dashboard routes - should require auth middleware
$router->get('/dashboard', 'dashboard/index.php')->only('auth');
$router->post('/dashboard/update', 'dashboard/update.php')->only(['auth', 'csrf']);
