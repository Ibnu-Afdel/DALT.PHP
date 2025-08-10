<?php

global $router;

// Main routes
$router->get('/', 'index.php');
$router->get('/about', 'about.php');
$router->get('/contact', 'contact.php');
$router->get('/test','test.php');

// Authentication routes
$router->get('/register', 'registration/create.php')->only('guest');
$router->post('/register', 'registration/store.php');

$router->get('/login', 'session/create.php')->only('guest');
$router->post('/session', 'session/store.php')->only('guest');
$router->delete('/session', 'session/destroy.php')->only('auth');

// post routes
$router->get('/post', 'post/index.php');
$router->get('/post/create', 'post/create.php');
$router->post('/post', 'post/store.php');
$router->get('/post/{id}', 'post/show.php');
$router->get('/post/{id}/edit', 'post/edit.php');
$router->patch('/post/{id}', 'post/update.php');
$router->delete('/post/{id}', 'post/destroy.php');
