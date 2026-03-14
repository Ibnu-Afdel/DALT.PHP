<?php

$db = \Core\App::resolve(\Core\Database::class);
$id = $_GET['id'] ?? 1;

// This uses parameter binding correctly, but will fail because
// Database::query() doesn't pass $params to execute()
$post = $db->query('SELECT * FROM posts WHERE id = :id', [
    'id' => $id
])->find();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .error { background: #fee; border: 1px solid #fcc; padding: 15px; border-radius: 5px; color: #c00; }
        .post { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <?php if (!$post): ?>
        <div class="error">
            <h1>Query Failed!</h1>
            <p>The query uses parameter binding correctly, but Database::query() doesn't pass parameters to execute().</p>
        </div>
    <?php else: ?>
        <div class="post">
            <h1><?= htmlspecialchars($post['title'] ?? 'Untitled') ?></h1>
            <p><?= htmlspecialchars($post['body'] ?? '') ?></p>
        </div>
    <?php endif; ?>
</body>
</html>
