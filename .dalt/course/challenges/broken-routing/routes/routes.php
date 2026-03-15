<?php

global $router;

$router->get('/', 'welcome.php');

// Posts routes
$router->get('/posts', 'posts/index.php');
// BUG: Route order issue - specific route after generic route with parameter
$router->get('/posts/{id}', 'posts/show.php');
$router->get('/posts/create', 'posts/create.php'); // This will never match!

// BUG: Missing route registration - edit route commented out
// $router->get('/posts/{id}/edit', 'posts/edit.php');

// About page
$router->get('/about', 'about.php');
