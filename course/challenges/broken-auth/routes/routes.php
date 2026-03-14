<?php

global $router;

$router->get('/', 'welcome.php');

// Auth routes
$router->get('/auth/login', 'auth/login.php')->only('guest');
$router->post('/auth/login', 'auth/login-post.php')->only(['guest', 'csrf']);
$router->get('/auth/register', 'auth/register.php')->only('guest');
$router->post('/auth/register', 'auth/register-post.php')->only(['guest', 'csrf']);

// Dashboard (requires auth)
$router->get('/dashboard', 'dashboard/index.php')->only('auth');
