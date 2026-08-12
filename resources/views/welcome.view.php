<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DALT.PHP</title>
  <style>
    :root { --bg:#0a0d12; --surface:#11161d; --border:#1e293b; --text:#f3f4f6; --muted:#94a3b8; --accent:#93da97; }
    * { box-sizing:border-box; } body { margin:0; min-height:100vh; background:var(--bg); color:var(--text); font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; } a { color:inherit; }
    .shell { min-height:100vh; display:flex; flex-direction:column; } .nav,.content,.footer { width:min(1120px,100% - 40px); margin:auto; } .nav { display:flex; align-items:center; justify-content:space-between; padding:20px 0; } .brand { display:flex; gap:10px; align-items:center; font-weight:800; letter-spacing:-.03em; text-decoration:none; } .mark { height:24px; width:8px; border-radius:6px; background:var(--accent); } .nav a:last-child { color:var(--accent); text-decoration:none; font-size:14px; font-weight:650; }
    .content { flex:1; padding:80px 0; } .eyebrow { color:var(--accent); font:700 12px ui-monospace,SFMono-Regular,Menlo,monospace; letter-spacing:.14em; text-transform:uppercase; } h1 { max-width:800px; margin:18px 0 0; font-size:clamp(44px,7vw,78px); line-height:1; letter-spacing:-.065em; } .intro { max-width:640px; margin:24px 0 0; color:var(--muted); font-size:19px; line-height:1.6; } .actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:32px; } .button { border:1px solid var(--border); border-radius:9px; padding:12px 16px; text-decoration:none; font-size:14px; font-weight:700; transition:.2s; } .primary { border-color:var(--accent); background:var(--accent); color:#0a0d12; } .primary:hover { background:#b5edb8; } .secondary { color:#d1d5db; background:var(--surface); } .secondary:hover { border-color:#506074; }
    .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:80px; } .item { border-top:1px solid var(--border); padding-top:18px; } .item strong { display:block; margin-bottom:8px; font-size:15px; } .item p { margin:0; color:var(--muted); font-size:14px; line-height:1.6; } .terminal { margin-top:12px; border-radius:8px; background:#080b0f; border:1px solid var(--border); padding:12px; color:var(--accent); font:13px ui-monospace,SFMono-Regular,Menlo,monospace; }
    .footer { border-top:1px solid var(--border); padding:20px 0 28px; color:#64748b; font-size:12px; } @media (max-width:700px) { .content { padding:48px 0; } .grid { grid-template-columns:1fr; margin-top:56px; } }
  </style>
</head>
<body>
  <?php $platformInstalled = function_exists('base_path') && is_dir(base_path('.dalt')); ?>
  <div class="shell">
    <header class="nav"><a class="brand" href="/"><span class="mark"></span>DALT.PHP</a><?php if ($platformInstalled): ?><a href="/learn">Open learning →</a><?php endif; ?></header>
    <main class="content">
      <p class="eyebrow">Transparent PHP framework</p>
      <h1>Build backends you can actually understand.</h1>
      <p class="intro">DALT.PHP keeps routing, middleware, sessions, validation, and SQL readable—so the framework helps you learn instead of hiding the work.</p>
      <div class="actions">
        <?php if ($platformInstalled): ?><a class="button primary" href="/learn">Start learning <span aria-hidden="true">→</span></a><a class="button secondary" href="/learn/resources">Browse resources</a><?php else: ?><a class="button primary" href="https://dalt.ibnuafdel.com/docs" target="_blank" rel="noopener noreferrer">Read the documentation <span aria-hidden="true">→</span></a><?php endif; ?>
        <a class="button secondary" href="https://github.com/ibnu-Afdel/dALT.PHP" target="_blank" rel="noopener noreferrer">View on GitHub</a>
      </div>
      <section class="grid" aria-label="Framework principles">
        <article class="item"><strong>Readable by design</strong><p>Follow a request from route to response without an opaque layer in the way.</p></article>
        <article class="item"><strong>Learn by building</strong><p>Use the optional course to connect concepts with debugging practice.</p></article>
        <article class="item"><strong>Your framework, your call</strong><p>The learning platform is optional and can be removed when you want a clean project.</p><?php if ($platformInstalled): ?><div class="terminal">php artisan platform:remove</div><?php endif; ?></article>
      </section>
    </main>
    <footer class="footer">DALT.PHP — clarity over magic.</footer>
  </div>
</body>
</html>
