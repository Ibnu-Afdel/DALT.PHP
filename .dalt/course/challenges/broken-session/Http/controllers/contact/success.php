<?php

$success = \Core\Session::get('success');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Success</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #efe; border: 1px solid #cfc; padding: 20px; border-radius: 5px; color: #060; }
        .error { background: #fee; border: 1px solid #fcc; padding: 20px; border-radius: 5px; color: #c00; }
    </style>
</head>
<body>
    <?php if ($success): ?>
        <div class="success">
            <h1>✓ <?= htmlspecialchars($success) ?></h1>
            <p>But if you refresh this page, the message will still appear (flash data not cleaned up!).</p>
        </div>
    <?php else: ?>
        <div class="error">
            <h1>No success message!</h1>
            <p>Flash data was not retrieved correctly.</p>
        </div>
    <?php endif; ?>

    <p><a href="/contact">← Back to form</a></p>
</body>
</html>
