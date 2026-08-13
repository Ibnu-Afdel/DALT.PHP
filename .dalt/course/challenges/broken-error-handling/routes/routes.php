<?php

global $router;

$router->get('/', 'welcome.php');

// Probe route for the ExceptionHandler verifier checks.
$router->get('/debug/render-error', 'debug/render-error.php');
