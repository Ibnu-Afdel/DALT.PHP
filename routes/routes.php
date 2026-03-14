<?php

global $router;

$router->get('/', 'welcome.php');

// Learning routes
$router->get('/learn', 'learn/index.php');
$router->get('/learn/lessons/{lesson}', 'learn/lesson.php');
$router->get('/learn/challenges/{challenge}', 'learn/challenge.php');

// API routes for verification
$router->post('/api/verify/{challenge}', 'api/verify.php');

// To install the auth example, run:
//   php artisan example:install auth


