<?php

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$auth = new \Core\Authenticator();

if ($auth->attempt($email, $password)) {
    redirect('/dashboard');
} else {
    redirect('/auth/login?error=1');
}
