<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DALT.PHP</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: system-ui, sans-serif; margin: 0; padding: 2rem; background: #0f1117; color: #d1d5db; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .container { max-width: 32rem; text-align: center; }
    h1 { font-size: 1.75rem; color: #fff; margin-bottom: 0.5rem; }
    p { color: #9ca3af; margin-bottom: 1.5rem; }
    a { color: #93DA97; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container">
    <h1>DALT.PHP</h1>
    <p>Your backend framework is ready.</p>
    <?php if (function_exists('base_path') && is_dir(base_path('.dalt'))): ?>
    <p><a href="/learn">Open Course &rarr;</a></p>
    <?php endif; ?>
  </div>
</body>
</html>
