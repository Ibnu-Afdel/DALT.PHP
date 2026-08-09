<?php

use Core\Authenticator;
use Core\Validator;
use Core\ValidationException;

$emailInput = $_POST['email'] ?? null;
$passwordInput = $_POST['password'] ?? null;
$email = is_string($emailInput) ? trim($emailInput) : '';
$password = is_string($passwordInput) ? $passwordInput : '';

$errors = [];
if (!Validator::email($email)) $errors['email'] = 'Valid email required';
if (!Validator::string($password, 1, 72)) $errors['password'] = 'Password must be between 1 and 72 characters';

if (!empty($errors)) {
    ValidationException::throw($errors, ['email' => $email]);
}

$auth = new Authenticator();
if ($auth->attempt($email, $password)) {
    return redirect($auth->intended());
}

ValidationException::throw(['email' => 'Invalid credentials'], ['email' => $email]);
