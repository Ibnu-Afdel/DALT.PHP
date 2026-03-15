<?php

$user = $_SESSION['user'] ?? null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #efe; border: 1px solid #cfc; padding: 20px; border-radius: 5px; }
        .nav { margin-bottom: 30px; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">← Home</a>
    </div>

    <div class="success">
        <h1>✓ Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($user['email'] ?? 'Guest') ?>!</p>
        <p>This page should only be accessible to authenticated users.</p>
        <p><strong>If you can see this without logging in, the Auth middleware is broken!</strong></p>
    </div>

    <form method="POST" action="/dashboard/update">
        <?= csrf_field() ?>
        <h2>Update Profile</h2>
        <p>Try submitting this form - CSRF middleware should validate the token.</p>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
