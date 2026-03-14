<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body { font-family: sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        form { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        input { width: 100%; padding: 8px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #0066cc; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0052a3; }
        .success { background: #efe; border: 1px solid #cfc; padding: 10px; border-radius: 5px; color: #060; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Register</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="success">
            Registration successful! Try logging in.
        </div>
    <?php endif; ?>

    <form method="POST" action="/auth/register">
        <?= csrf_field() ?>
        
        <label>Email:</label>
        <input type="email" name="email" required>
        
        <label>Password:</label>
        <input type="password" name="password" required>
        
        <button type="submit">Register</button>
    </form>

    <p><a href="/auth/login">Already have an account? Login</a></p>
</body>
</html>
