<?php

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$auth = new \Core\Authenticator();

if ($auth->attempt($email, $password)) {
    return redirect('/dashboard');
} else {
    return redirect('/auth/login?error=1');
}
