<?php

$db = \Core\App::resolve(\Core\Database::class);

// BUG: SQL injection vulnerability - user input concatenated directly!
$search = $_GET['search'] ?? '';
if ($search) {
    // DANGEROUS: No parameter binding!
    $query = "SELECT * FROM posts WHERE title LIKE '%" . $search . "%'";
    $posts = $db->query($query)->get();
} else {
    $posts = $db->query('SELECT * FROM posts')->get();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Posts</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .post { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>All Posts</h1>

    <div class="warning">
        <strong>⚠️ SQL Injection Vulnerability!</strong><br>
        Try: <code>?search=1' OR '1'='1</code>
    </div>

    <form method="GET">
        <input type="text" name="search" placeholder="Search posts..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <?php if (empty($posts)): ?>
        <p>No posts found. Run migrations to create sample data.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <h2><?= htmlspecialchars($post['title'] ?? 'Untitled') ?></h2>
                <p><?= htmlspecialchars($post['body'] ?? '') ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
