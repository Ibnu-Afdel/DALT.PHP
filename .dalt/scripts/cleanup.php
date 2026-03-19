#!/usr/bin/env php
<?php

/**
 * DALT.PHP Cleanup Script
 * 
 * This script removes the learning platform and converts DALT.PHP
 * into a clean, standalone micro-framework.
 * 
 * Usage: php .dalt/scripts/cleanup.php
 */

define('BASE_PATH', dirname(__DIR__, 2) . '/');

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         DALT.PHP → Clean Framework Conversion              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "This will remove the learning platform and convert DALT.PHP\n";
echo "into a clean micro-framework for building your own apps.\n";
echo "\n";
echo "The following will be removed:\n";
echo "  • .dalt/ directory (learning platform)\n";
echo "  • Learning-specific npm scripts\n";
echo "  • Challenge verification commands\n";
echo "\n";
echo "The following will be kept:\n";
echo "  • framework/Core/ (router, database, session, etc.)\n";
echo "  • app/ directory (your controllers)\n";
echo "  • routes/routes.php (your routes)\n";
echo "  • All configuration files\n";
echo "\n";

// Ask for confirmation
echo "Do you want to continue? [y/N]: ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'y' && strtolower($line) !== 'yes') {
    echo "\nCancelled. No changes made.\n\n";
    exit(0);
}

echo "\n";
echo "Starting cleanup...\n";
echo "\n";

// Step 1: Remove .dalt directory
echo "[1/5] Removing .dalt directory... ";
if (is_dir(BASE_PATH . '.dalt')) {
    removeDirectory(BASE_PATH . '.dalt');
    echo "✓\n";
} else {
    echo "⊘ (already removed)\n";
}

// Step 2: Clean up package.json
echo "[2/5] Cleaning package.json... ";
$packageJsonPath = BASE_PATH . 'package.json';
if (file_exists($packageJsonPath)) {
    $packageJson = json_decode(file_get_contents($packageJsonPath), true);
    
    // Update description
    $packageJson['description'] = 'A lightweight PHP micro-framework';
    
    // Remove learning-specific keywords
    $packageJson['keywords'] = array_values(array_diff(
        $packageJson['keywords'] ?? [],
        ['learning', 'education', 'debugging', 'tutorial']
    ));
    
    // Keep only essential keywords
    if (empty($packageJson['keywords'])) {
        $packageJson['keywords'] = ['php', 'framework', 'micro-framework'];
    }
    
    file_put_contents($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "✓\n";
} else {
    echo "⊘ (not found)\n";
}

// Step 3: Clean up composer.json
echo "[3/5] Cleaning composer.json... ";
$composerJsonPath = BASE_PATH . 'composer.json';
if (file_exists($composerJsonPath)) {
    $composerJson = json_decode(file_get_contents($composerJsonPath), true);
    
    // Update description
    $composerJson['description'] = 'A lightweight PHP micro-framework for building web applications';
    
    // Remove learning-specific keywords
    $composerJson['keywords'] = array_values(array_diff(
        $composerJson['keywords'] ?? [],
        ['learning', 'education', 'debugging', 'tutorial', 'beginner-friendly']
    ));
    
    // Remove post-install scripts that reference .dalt
    if (isset($composerJson['scripts']['post-root-package-install'])) {
        $composerJson['scripts']['post-root-package-install'] = array_filter(
            $composerJson['scripts']['post-root-package-install'],
            fn($script) => !str_contains($script, '.dalt')
        );
        if (empty($composerJson['scripts']['post-root-package-install'])) {
            unset($composerJson['scripts']['post-root-package-install']);
        }
    }
    
    if (isset($composerJson['scripts']['post-create-project-cmd'])) {
        unset($composerJson['scripts']['post-create-project-cmd']);
    }
    
    file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "✓\n";
} else {
    echo "⊘ (not found)\n";
}

// Step 4: Update vite.config.mjs (remove if no frontend needed)
echo "[4/5] Cleaning vite.config.mjs... ";
$viteConfigPath = BASE_PATH . 'vite.config.mjs';
if (file_exists($viteConfigPath)) {
    // Create a minimal vite config for user's own frontend
    $minimalViteConfig = <<<'JS'
import { defineConfig } from 'vite'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const __dirname = dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  root: __dirname,
  base: '/',
  publicDir: 'public',
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },
  build: {
    outDir: 'public/build',
    manifest: true,
    rollupOptions: {
      input: 'resources/js/app.js'
    }
  }
})
JS;
    
    file_put_contents($viteConfigPath, $minimalViteConfig . "\n");
    echo "✓\n";
} else {
    echo "⊘ (not found)\n";
}

// Step 5: Update README
echo "[5/5] Creating clean README... ";
$readmePath = BASE_PATH . 'README.md';
$cleanReadme = <<<'MD'
# DALT.PHP — Lightweight PHP Micro-Framework

A simple, elegant PHP micro-framework for building web applications.

## Features

- **Simple Routing** - Clean, expressive route definitions
- **Middleware** - Request filtering and authentication
- **Database** - PDO-based database abstraction with query builder
- **Session Management** - Secure session handling with flash data
- **Template System** - Simple PHP-based views
- **Modern Stack** - PHP 8.2+, PSR-4 autoloading

## Quick Start

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env

# Start development server
php artisan serve
```

Visit `http://localhost:8000`

## Project Structure

```
app/
  Http/controllers/     # Your controllers
config/                 # Configuration files
database/               # Database and migrations
framework/Core/         # Framework core
public/                 # Web root
resources/views/        # Your views
routes/routes.php       # Route definitions
storage/                # Logs and cache
```

## Basic Usage

### Define Routes

```php
// routes/routes.php
$router->get('/', 'welcome.php');
$router->get('/posts/{id}', 'posts/show.php');
$router->post('/posts', 'posts/store.php')->only('csrf');
```

### Create Controllers

```php
// app/Http/controllers/posts/show.php
$db = \Core\App::resolve(\Core\Database::class);
$post = $db->query('SELECT * FROM posts WHERE id = :id', ['id' => $_GET['id']])->findOrFail();

view('posts/show.view.php', ['post' => $post]);
```

### Create Views

```php
<!-- resources/views/posts/show.view.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= $post['title'] ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <p><?= htmlspecialchars($post['body']) ?></p>
</body>
</html>
```

## Documentation

- [Routing](docs/routing.md)
- [Database](docs/database.md)
- [Middleware](docs/middleware.md)
- [Sessions](docs/sessions.md)

## License

MIT License - see LICENSE file for details.
MD;

file_put_contents($readmePath, $cleanReadme . "\n");
echo "✓\n";

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    Cleanup Complete!                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "DALT.PHP is now a clean micro-framework.\n";
echo "\n";
echo "Next steps:\n";
echo "  1. Review routes/routes.php and add your routes\n";
echo "  2. Create controllers in app/Http/controllers/\n";
echo "  3. Create views in resources/views/\n";
echo "  4. Build something awesome!\n";
echo "\n";
echo "The original README has been backed up to README.learning.md\n";
echo "\n";

// Backup original README
if (file_exists(BASE_PATH . 'README.md')) {
    copy(BASE_PATH . 'README.md', BASE_PATH . 'README.learning.md');
}

exit(0);

/**
 * Recursively remove a directory
 */
function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $items = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
}
