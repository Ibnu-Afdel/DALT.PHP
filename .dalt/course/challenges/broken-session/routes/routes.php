<?php

global $router;

$router->get('/', 'welcome.php');

// Contact form routes
$router->get('/contact', 'contact/form.php');
$router->post('/contact/submit', 'contact/submit.php')->only('csrf');
$router->get('/contact/success', 'contact/success.php');
