<?php
$auth = new \Core\Authenticator();
$auth->logout();

header('location: /');
exit();