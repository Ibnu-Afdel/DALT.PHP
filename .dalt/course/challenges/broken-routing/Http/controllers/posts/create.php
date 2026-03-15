<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .nav { margin-bottom: 30px; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .success { background: #efe; border: 1px solid #cfc; padding: 15px; border-radius: 5px; color: #060; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">← Home</a> | 
        <a href="/posts">All Posts</a>
    </div>

    <div class="success">
        <h1>✓ Success!</h1>
        <p>You reached the Create Post page. This route should work, but it's currently broken due to route ordering.</p>
        <p><strong>Expected:</strong> /posts/create should load this page</p>
        <p><strong>Actual:</strong> /posts/create matches /posts/{id} instead, treating "create" as an ID</p>
    </div>
</body>
</html>
