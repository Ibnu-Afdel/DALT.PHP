<?php

$errors = \Core\Session::get('errors', []);
$oldName = old('name');
$oldEmail = old('email');
$oldMessage = old('message');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Form</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        form { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        input, textarea { width: 100%; padding: 8px; margin: 10px 0; box-sizing: border-box; }
        button { padding: 10px 20px; background: #0066cc; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { background: #fee; border: 1px solid #fcc; padding: 10px; border-radius: 5px; color: #c00; margin-bottom: 15px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Contact Form</h1>

    <div class="warning">
        <strong>⚠️ Session Bug!</strong><br>
        Submit the form with errors - flash messages and old input won't work correctly.
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>Errors:</strong>
            <ul>
                <?php foreach ($errors as $field => $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/contact/submit">
        <?= csrf_field() ?>
        
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($oldName) ?>">
        
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($oldEmail) ?>">
        
        <label>Message:</label>
        <textarea name="message" rows="5"><?= htmlspecialchars($oldMessage) ?></textarea>
        
        <button type="submit">Submit</button>
    </form>

    <p><small>Try submitting with empty fields to see validation errors.</small></p>
</body>
</html>
