<?php

use Core\App;
use Core\Database;
use Core\Session;

$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['password_confirmation'] ?? '';

$errors = [];
if ($name === '') $errors['name'] = 'Name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email required';
if (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters';
if ($password !== $confirm) $errors['password_confirmation'] = 'Passwords do not match';

if (!empty($errors)) {
    Session::flash('errors', $errors);
    Session::flash('old', ['name' => $name, 'email' => $email]);
    redirect('/register');
}

$db = App::resolve(Database::class);

$existing = $db->query('SELECT 1 FROM users WHERE email = :email', ['email' => $email])->find();
if ($existing) {
    Session::flash('errors', ['email' => 'Email already taken']);
    Session::flash('old', ['name' => $name, 'email' => $email]);
    redirect('/register');
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