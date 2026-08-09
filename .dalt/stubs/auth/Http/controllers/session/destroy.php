<?php

use Core\Authenticator;

$auth = new Authenticator();
$auth->logout();

return redirect('/');
