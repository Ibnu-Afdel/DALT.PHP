<?php

$id = $_GET['id'] ?? null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .nav { margin-bottom: 30px; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .error { background: #fee; border: 1px solid #fcc; padding: 15px; border-radius: 5px; color: #c00; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">← Home</a> | 
        <a href="/posts">All Posts</a>
    </div>

    <div class="error">
        <h1>✗ 404 Error</h1>
        <p>This page should exist but the route is not registered!</p>
        <p><strong>Expected:</strong> /posts/<?= $id ?>/edit should load this page</p>
        <p><strong>Actual:</strong> Route is commented out in routes.php</p>
    </div>
</body>
</html>
