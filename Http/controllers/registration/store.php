<?php

use Core\App;
use Core\Database;
use Core\Validator;
use Core\ValidationException;
use Core\Authenticator;

$db = App::resolve(Database::class);

$name = $_POST["name"];
$email = $_POST["email"];
$password = $_POST["password"];

$errors = [];

if (! Validator::string($name, 1, 255)) {
    $errors['name'] = "Name is required and must be between 1 and 255 characters";
}

if (! Validator::email($email)) {
    $errors['email'] = "Invalid email";
}

if (! Validator::string($password, 7, 255)) {
    $errors['password'] = "minimum of 7 and maximum of 255 characters allowed";
}

if (! empty($errors)) {
    ValidationException::throw($errors, $_POST);
}

$user = $db->query("SELECT * FROM users WHERE email = :email", [":email" => $email])->find();

if($user) {
    // User already exists, show error and redirect back to registration form
    ValidationException::throw(['email' => 'A user with that email already exists.'], $_POST);
}

// Create new user
$db->query("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)", [
    'name' => $name,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT)
]);

// Login the newly created user
(new Authenticator)->login([
    'email' => $email,
]);

redirect('/');
