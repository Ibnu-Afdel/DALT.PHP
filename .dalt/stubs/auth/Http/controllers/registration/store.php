<?php

use Core\App;
use Core\Database;
use Core\Session;
use Core\Validator;
use Core\ValidationException;

$emailInput = $_POST['email'] ?? null;
$nameInput = $_POST['name'] ?? null;
$passwordInput = $_POST['password'] ?? null;
$confirmationInput = $_POST['password_confirmation'] ?? null;
$email = is_string($emailInput) ? trim($emailInput) : '';
$name = is_string($nameInput) ? trim($nameInput) : '';
$password = is_string($passwordInput) ? $passwordInput : '';
$confirm = is_string($confirmationInput) ? $confirmationInput : '';

$errors = [];
if (!Validator::string($name)) $errors['name'] = 'Name is required';
if (!Validator::email($email)) $errors['email'] = 'Valid email required';
if (!Validator::string($password, 8, 72)) $errors['password'] = 'Password must be between 8 and 72 characters';
if ($password !== $confirm) $errors['password_confirmation'] = 'Passwords do not match';

if (!empty($errors)) {
    ValidationException::throw($errors, ['name' => $name, 'email' => $email]);
}

$db = App::resolve(Database::class);

$existing = $db->query('SELECT 1 FROM users WHERE email = :email', ['email' => $email])->find();
if ($existing) {
    ValidationException::throw(['email' => 'Email already taken'], ['name' => $name, 'email' => $email]);
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');
$db->query('INSERT INTO users (name, email, password, created_at, updated_at) VALUES (:name, :email, :password, :created_at, :updated_at)', [
    'name' => $name,
    'email' => $email,
    'password' => $hashed,
    'created_at' => $now,
    'updated_at' => $now,
]);

Session::flash('success', 'Registration successful. You can now log in.');
return redirect('/login');
