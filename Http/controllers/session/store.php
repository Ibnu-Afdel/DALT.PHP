<?php

use Core\Authenticator;
use Core\Validator;
use Core\ValidationException;

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];
if (!Validator::email($email)) $errors['email'] = 'Valid email required';
if (!Validator::string($password)) $errors['password'] = 'Password is required';

if (!empty($errors)) {
    ValidationException::throw($errors, ['email' => $email]);
}

$auth = new Authenticator();
if ($auth->attempt($email, $password)) {
    redirect('/');
}

ValidationException::throw(['email' => 'Invalid credentials'], ['email' => $email]); 