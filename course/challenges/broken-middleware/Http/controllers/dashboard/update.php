<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Updated</title>
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
        <a href="/">← Home</a> | <a href="/dashboard">Dashboard</a>
    </div>

    <div class="success">
        <h1>✓ Profile Updated</h1>
        <p>If you see this page, CSRF validation passed!</p>
        <p><strong>But wait - the CSRF middleware is broken and rejecting valid tokens!</strong></p>
    </div>
</body>
</html>
