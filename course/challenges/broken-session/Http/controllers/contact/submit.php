<?php

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

// Validate
$errors = [];
if (empty($name)) {
    $errors['name'] = 'Name is required';
}
if (empty($email)) {
    $errors['email'] = 'Email is required';
}
if (empty($message)) {
    $errors['message'] = 'Message is required';
}

if (!empty($errors)) {
    // Flash errors and old input
    \Core\Session::flash('errors', $errors);
    \Core\Session::flash('old', $_POST);
    
    redirect('/contact');
}

// Success
\Core\Session::flash('success', 'Message sent successfully!');
redirect('/contact/success');
