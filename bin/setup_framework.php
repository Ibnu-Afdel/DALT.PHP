#!/usr/bin/env php
<?php

echo "🚀 Setting up your PHP Framework...\n\n";

// Step 1: Copy .env.example to .env
echo "📋 Copying environment file...\n";
if (!file_exists('.env')) {
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        echo "✅ .env file created from .env.example\n";
    } else {
        echo "❌ .env.example not found!\n";
        exit(1);
    }
} else {
    echo "✅ .env file already exists\n";
}

// Step 2: Install Composer dependencies
echo "\n📦 Installing Composer dependencies...\n";
system('composer install --no-dev --optimize-autoloader');

// Step 3: Install npm packages
echo "\n📦 Installing npm packages...\n";
if (file_exists('package.json')) {
    system('npm install');
    echo "✅ npm packages installed\n";
} else {
    echo "❌ package.json not found!\n";
    exit(1);
}

// Step 4: Build assets
echo "\n🔨 Building assets...\n";
system('npm run build');

// Step 5: Make scripts executable (Unix systems)
echo "\n🔧 Making scripts executable...\n";
$scripts = ['../artisan', 'migrate_fresh.php', 'setup_framework.php', 'seed.php'];
foreach ($scripts as $script) {
    if (file_exists($script)) {
        chmod($script, 0755);
        echo "✅ Made $script executable\n";
    }
}

// Step 6: Create necessary directories
echo "\n📁 Creating directories...\n";
$directories = [
    '../database/migrations',
    '../database/seeds',
    '../public/css',
    '../public/js',
    '../storage/logs',
    '../routes'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Created directory: $dir\n";
    } else {
        echo "✅ Directory already exists: $dir\n";
    }
}

// Step 7: Run initial migration
echo "\n🗄️ Setting up database...\n";
system('cd .. && php artisan migrate');

// Step 8: Seed database
echo "\n🌱 Seeding database...\n";
system('cd .. && php bin/seed.php');

// Step 9: Show success message
echo "\n🎉 Setup complete!\n\n";
echo "🛠️  Available commands:\n";
echo "   php artisan migrate           - Run migrations\n";
echo "   php artisan migrate:fresh     - Fresh migration\n";
echo "   php artisan make:migration    - Create migration\n";
echo "   npm run dev                   - Watch for changes\n";
echo "   npm run build                 - Build for production\n";
echo "   php -S localhost:8000 -t public  - Start dev server\n";
echo "\n🔗 Visit: http://localhost:8000\n";
echo "📧 Default login: test@example.com / password123\n\n";
echo "Happy coding! 🚀\n";
