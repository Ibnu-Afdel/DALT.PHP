<?php

use Core\App;
use Core\Database;
use Core\Session;
use Core\Validator;
use Core\ValidationException;

$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['password_confirmation'] ?? '';

$errors = [];
if (!Validator::string($name)) $errors['name'] = 'Name is required';
if (!Validator::email($email)) $errors['email'] = 'Valid email required';
if (!Validator::string($password, 6)) $errors['password'] = 'Password must be at least 6 characters';
if ($password !== $confirm) $errors['password_confirmation'] = 'Passwords do not match';

if (!empty($errors)) {
    ValidationException::throw($errors, ['name' => $name, 'email' => $email]);
}

$db = App::resolve(Database::class);

$existing = $db->query('SELECT 1 FROM users WHERE email = :email', ['email' => $email])->find();
if ($existing) {
    ValidationException::throw(['email' => 'Email already taken'], ['name' => $name, 'email' => $email]);
}

$hashed = password_hash($password, PASSWORD_BCRYPT);
$now = date('Y-m-d H:i:s');
$db->query('INSERT INTO users (name, email, password, created_at, updated_at) VALUES (:name, :email, :password, :created_at, :updated_at)', [
    'name' => $name,
    'email' => $email,
    'password' => $hashed,
    'created_at' => $now,
    'updated_at' => $now,
]);

Session::flash('success', 'Registration successful. You can now log in.');
redirect('/login'); 