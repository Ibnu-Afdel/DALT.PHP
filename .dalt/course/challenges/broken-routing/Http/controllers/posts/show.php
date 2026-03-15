<?php

// Get post ID from route parameter
$id = $_GET['id'] ?? null;

if (!$id) {
    echo "Error: No post ID provided";
    exit;
}

// Mock post data
$posts = [
    1 => ['id' => 1, 'title' => 'First Post', 'body' => 'This is the first post content.'],
    2 => ['id' => 2, 'title' => 'Second Post', 'body' => 'This is the second post content.'],
    3 => ['id' => 3, 'title' => 'Third Post', 'body' => 'This is the third post content.'],
];

$post = $posts[$id] ?? null;

if (!$post) {
    echo "Error: Post not found";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .post { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        .nav { margin-bottom: 30px; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">← Home</a> | 
        <a href="/posts">All Posts</a> | 
        <a href="/posts/<?= $post['id'] ?>/edit">Edit</a>
    </div>

    <div class="post">
        <h1><?= htmlspecialchars($post['title']) ?></h1>
        <p><?= htmlspecialchars($post['body']) ?></p>
    </div>
</body>
</html>
