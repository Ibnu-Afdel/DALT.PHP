<?php

// List all posts
$posts = [
    ['id' => 1, 'title' => 'First Post', 'body' => 'This is the first post'],
    ['id' => 2, 'title' => 'Second Post', 'body' => 'This is the second post'],
    ['id' => 3, 'title' => 'Third Post', 'body' => 'This is the third post'],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Posts</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .post { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .post h2 { margin-top: 0; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .nav { margin-bottom: 30px; }
        .error { background: #fee; border: 1px solid #fcc; padding: 10px; border-radius: 5px; color: #c00; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">← Home</a> | 
        <a href="/posts">All Posts</a> | 
        <a href="/posts/create">Create Post</a>
    </div>

    <h1>All Posts</h1>

    <?php foreach ($posts as $post): ?>
        <div class="post">
            <h2><?= htmlspecialchars($post['title']) ?></h2>
            <p><?= htmlspecialchars($post['body']) ?></p>
            <a href="/posts/<?= $post['id'] ?>">View Post</a> | 
            <a href="/posts/<?= $post['id'] ?>/edit">Edit Post</a>
        </div>
    <?php endforeach; ?>
</body>
</html>
