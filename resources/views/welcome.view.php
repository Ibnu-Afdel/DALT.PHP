<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DALT.PHP</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: system-ui, sans-serif; margin: 0; padding: 2.5rem 1.25rem; background: #0f1117; color: #d1d5db; min-height: 100vh; }
    .container { max-width: 56rem; margin: 0 auto; }
    .header { text-align: left; margin-bottom: 1.5rem; }
    h1 { font-size: 2rem; line-height: 1.2; color: #fff; margin: 0 0 0.5rem; }
    .subtitle { color: #9ca3af; margin: 0; max-width: 52rem; }
    .grid { display: grid; gap: 1rem; grid-template-columns: 1fr; margin-top: 1.5rem; }
    @media (min-width: 840px) { .grid { grid-template-columns: 1fr 1fr; } }
    .card { border: 1px solid rgba(148, 163, 184, 0.15); background: rgba(17, 24, 39, 0.55); border-radius: 14px; padding: 1.25rem; }
    .card h2 { margin: 0 0 0.5rem; color: #fff; font-size: 1.1rem; }
    .card p { margin: 0.25rem 0 0.75rem; color: #9ca3af; line-height: 1.6; }
    .actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
    .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.8rem; border-radius: 10px; border: 1px solid rgba(148, 163, 184, 0.2); background: rgba(148, 163, 184, 0.08); color: #e5e7eb; text-decoration: none; font-weight: 600; }
    .btn:hover { background: rgba(148, 163, 184, 0.14); }
    .btn.primary { border-color: rgba(147, 218, 151, 0.35); background: rgba(147, 218, 151, 0.12); }
    .btn.primary:hover { background: rgba(147, 218, 151, 0.18); }
    .code { margin: 0.75rem 0 0; padding: 0.85rem 1rem; background: rgba(2, 6, 23, 0.6); border: 1px solid rgba(148, 163, 184, 0.16); border-radius: 12px; overflow: auto; }
    .code code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; color: #e5e7eb; }
    .footer { margin-top: 1.5rem; color: #6b7280; font-size: 0.95rem; }
    .footer a { color: #93DA97; text-decoration: none; }
    .footer a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>DALT.PHP is ready</h1>
      <p class="subtitle">A transparent PHP framework for learning backend development. Routing, sessions, auth, validation, and database access are all there — in readable code you can follow.</p>
    </div>

    <div class="grid">
      <section class="card">
        <h2>Start Here</h2>
        <p>Read the docs and follow a guide to build something real.</p>
        <div class="actions">
          <a class="btn primary" href="https://dalt.ibnuafdel.com/docs" target="_blank" rel="noreferrer">Docs</a>
          <a class="btn" href="https://dalt.ibnuafdel.com/docs/guides/building-a-blog" target="_blank" rel="noreferrer">Build a Blog</a>
          <a class="btn" href="https://dalt.ibnuafdel.com/docs/guides/building-an-api" target="_blank" rel="noreferrer">Build an API</a>
          <a class="btn" href="https://t.me/daltphp" target="_blank" rel="noreferrer">Telegram</a>
        </div>
      </section>

      <section class="card">
        <h2>Guided Learning (Optional)</h2>
        <?php $platformInstalled = function_exists('base_path') && is_dir(base_path('.dalt')); ?>
        <?php if ($platformInstalled): ?>
          <p>Lessons and debugging challenges are installed. Open the course UI, or remove it if you want the framework core only.</p>
          <div class="actions">
            <a class="btn primary" href="/learn/start">Open Guided Learning →</a>
            <a class="btn" href="/learn">Open Course</a>
          </div>
          <div class="code"><code>php artisan platform:remove</code></div>
        <?php else: ?>
          <p>Guided learning is not installed. You can start building your app immediately.</p>
          <div class="code"><code>php artisan platform:status</code></div>
        <?php endif; ?>
      </section>
    </div>

    <div class="footer">
      <div>Need help? Join the community on <a href="https://t.me/daltphp" target="_blank" rel="noreferrer">Telegram</a>.</div>
    </div>
  </div>
</body>
</html>
