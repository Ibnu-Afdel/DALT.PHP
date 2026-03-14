<?php

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validate
if (empty($email) || empty($password)) {
    redirect('/auth/register?error=empty');
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$db = \Core\App::resolve(\Core\Database::class);
$db->query('INSERT INTO users (email, password) VALUES (:email, :password)', [
    'email' => $email,
    'password' => $hashedPassword
]);

// Auto-login after registration
$auth = new \Core\Authenticator();
$auth->login(['email' => $email]);

redirect('/dashboard');
