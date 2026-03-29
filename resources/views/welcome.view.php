<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DALT.PHP</title>
    <style>
        :root {
            --bg-color: #000000;
            --surface-color: #111111;
            --border-color: #333333;
            --text-main: #f5f5f5;
            --text-muted: #888888;
            --accent: #ffffff;
            --accent-hover: #e0e0e0;
        }
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            margin: 0; 
            padding: 0;
            background: var(--bg-color); 
            color: var(--text-main); 
            line-height: 1.5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { 
            max-width: 60rem; 
            margin: 0 auto; 
            width: 100%;
            padding: 2rem;
        }
        h1 { 
            font-size: 2.25rem; 
            font-weight: 700;
            line-height: 1.1; 
            letter-spacing: -0.04em;
            margin: 0 0 0.5rem; 
        }
        .subtitle { 
            color: var(--text-muted); 
            font-size: 1.05rem;
            margin: 0 0 1.5rem; 
            max-width: 44rem; 
        }
        .main-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 768px) {
            .main-grid { grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        }
        .panel {
            padding: 1.5rem;
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
        }
        h2 { 
            font-size: 1.15rem; 
            font-weight: 600;
            margin: 0 0 0.75rem; 
            color: var(--accent);
        }
        p { 
            margin: 0 0 1rem; 
            font-size: 0.95rem;
            color: var(--text-muted); 
            flex-grow: 1;
        }
        .grid-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        .link-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-main);
            font-size: 0.85rem;
            background: transparent;
            transition: all 0.2s;
        }
        .link-pill:hover { 
            border-color: var(--text-muted); 
            background: rgba(255, 255, 255, 0.05);
        }
        .btn { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            padding: 0.5rem 1rem; 
            border-radius: 6px; 
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none; 
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-primary { 
            background: var(--accent); 
            color: #000; 
            border: 1px solid var(--accent);
        }
        .btn-primary:hover { 
            background: var(--accent-hover); 
        }
        .btn-outline {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }
        .btn-outline:hover {
            border-color: var(--text-muted);
            background: rgba(255, 255, 255, 0.05);
        }
        .actions { 
            display: flex; 
            gap: 0.75rem; 
            flex-wrap: wrap;
            margin-top: auto;
        }
        .terminal { 
            margin: 0 0 1rem 0; 
            padding: 0.75rem 1rem; 
            background: #000; 
            border: 1px solid var(--border-color); 
            border-radius: 6px; 
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; 
            font-size: 0.85rem;
            color: #4ade80; 
        }
        .terminal::before { content: "$ "; color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="container">
        <h1>DALT.PHP is ready.</h1>
        <p class="subtitle">A transparent PHP framework designed to teach backend development concepts through readable, focused code.</p>

        <div class="grid-links">
            <a href="https://dalt.ibnuafdel.com/docs" class="link-pill" target="_blank">📚 Documentation</a>
            <a href="https://t.me/daltphp" class="link-pill" target="_blank">💬 Telegram Community</a>
            <a href="https://github.com/ibnu-Afdel/dALT.PHP" class="link-pill" target="_blank">⭐ GitHub</a>
        </div>

        <div class="main-grid">
            <?php $platformInstalled = function_exists('base_path') && is_dir(base_path('.dalt')); ?>
            <?php if ($platformInstalled): ?>
            <div class="panel">
                <h2>Guided Learning</h2>
                <p>DALT includes built-in interactive lessons. Dive in to grasp backend fundamentals directly within the framework.</p>
                <div class="actions">
                    <a class="btn btn-primary" href="/learn/start">Start Learning</a>
                    <a class="btn btn-outline" href="/learn">Course Dashboard</a>
                </div>
            </div>

            <div class="panel">
                <h2>Start Building</h2>
                <p>Prefer a clean slate? Remove the learning modules to get a minimal, barebones framework core:</p>
                <div class="terminal">php artisan platform:remove</div>
                
                <p>Then, learn by building! Explore the docs or follow these guides to master the framework flow:</p>
                <div class="actions">
                    <a class="btn btn-outline" href="https://dalt.ibnuafdel.com/docs/guides/building-a-blog" target="_blank">Build a Blog</a>
                    <a class="btn btn-outline" href="https://dalt.ibnuafdel.com/docs/guides/building-an-api" target="_blank">Build an API</a>
                </div>
            </div>
            <?php else: ?>
            <div class="panel">
                <h2>Build Your Application</h2>
                <p>The framework is clean and ready. Learn by building practical applications. Explore the docs or follow our step-by-step guides to master the framework flow:</p>
                <div class="actions">
                    <a class="btn btn-outline" href="https://dalt.ibnuafdel.com/docs/guides/building-a-blog" target="_blank">Build a Blog</a>
                    <a class="btn btn-outline" href="https://dalt.ibnuafdel.com/docs/guides/building-an-api" target="_blank">Build an API</a>
                </div>
            </div>
            <div class="panel">
                <h2>Guided Learning Removed</h2>
                <p>The learning modules are not installed in this project.</p>
                <div class="terminal">php artisan platform:status</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
