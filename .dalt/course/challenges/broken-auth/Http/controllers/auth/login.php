<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        form { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        input { width: 100%; padding: 8px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #0066cc; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0052a3; }
        .error { background: #fee; border: 1px solid #fcc; padding: 10px; border-radius: 5px; color: #c00; margin-bottom: 15px; }
        .info { background: #e7f3ff; border: 1px solid #b3d9ff; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Login</h1>

    <div class="info">
        <strong>Test Credentials:</strong><br>
        Email: test@example.com<br>
        Password: password123
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="error">
            Invalid credentials. Login always fails because password verification is broken!
        </div>
    <?php endif; ?>

    <form method="POST" action="/auth/login">
        <?= csrf_field() ?>
        
        <label>Email:</label>
        <input type="email" name="email" value="test@example.com" required>
        
        <label>Password:</label>
        <input type="password" name="password" value="password123" required>
        
        <button type="submit">Login</button>
    </form>

    <p><a href="/auth/register">Don't have an account? Register</a></p>
</body>
</html>
