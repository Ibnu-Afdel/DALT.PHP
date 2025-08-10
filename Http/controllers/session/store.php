<?php

use Core\Authenticator;
use Core\Session;

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$auth = new Authenticator();
if ($auth->attempt($email, $password)) {
    redirect('/');
}

Session::flash('errors', ['email' => 'Invalid credentials']);
Session::flash('old', ['email' => $email]);
redirect('/login'); 