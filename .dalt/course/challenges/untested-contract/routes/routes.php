<?php

global $router;

$router->get('/', 'welcome.php');

$router->post('/coupons/redeem', 'coupons/redeem.php');
