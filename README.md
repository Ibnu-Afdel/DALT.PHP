# DALT.PHP Framework

A modern PHP framework with Laravel-like features including migrations, Tailwind CSS, DaisyUI, and Alpine.js.

## 🚀 Quick Start

After cloning the repository, run just **one command** to set up everything:

```bash
composer setup
```

This will automatically:
- Copy `.env.example` to `.env`
- Install Composer dependencies
- Install npm packages (Tailwind CSS, DaisyUI, Alpine.js)
- Build CSS and JS assets
- Make scripts executable
- Create necessary directories
- Run initial database migration
- Seed database with test user

## 📋 Manual Setup (if needed)

If you prefer to set up manually:

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd DALT.PHP
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   ```

4. **Build assets**
   ```bash
   npm run build
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed database**
   ```bash
   composer seed
   ```

## 🛠️ Available Commands

### Migration Commands
```bash
php artisan migrate           # Run pending migrations
php artisan migrate:fresh     # Drop all tables and run migrations
php artisan make:migration posts  # Create a new migration
composer seed                 # Seed database with test data
```

### Asset Building
```bash
npm run dev                   # Watch for changes (development) - Auto-rebuilds CSS/JS
npm run watch                 # Same as dev (alias)
npm run build                 # Build for production
npm run prod                  # Build and minify for production
```

**Important:** Always run `npm run dev` during development to automatically rebuild your CSS and JavaScript files when you make changes. This eliminates the need to manually run `npm run build` every time.

### Development Server
```bash
php artisan serve              # Start server on localhost:8000
php artisan serve 127.0.0.1 3000  # Custom host and port
composer serve                 # Alternative way
```

### Testing
```bash
composer test                 # Run tests with Pest
```

## 📁 Project Structure

```
├── Core/                    # Framework core classes
├── Http/                    # Controllers and forms
├── resources/               # Views, CSS, JS
├── database/migrations/     # Database migrations
├── public/                  # Public web files
├── routes/                  # Application routes
│   └── routes.php
├── bin/                     # CLI scripts and tools
│   ├── migrate.php
│   ├── migrate_fresh.php
│   ├── seed.php
│   └── setup_framework.php
├── artisan                 # Main CLI tool
└── .env.example           # Environment configuration
```

## 🗄️ Database

The framework uses SQLite by default for easy development. You can switch to PostgreSQL or MySQL by updating your `.env` file:

```env
# SQLite (default)
DB_DRIVER=sqlite
DB_DATABASE=database/app.sqlite

# PostgreSQL
DB_DRIVER=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=dalt_php_app
DB_USERNAME=your_username
DB_PASSWORD=your_password

# MySQL
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=dalt_php_app
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 🎨 Frontend Stack

- **Tailwind CSS** - Utility-first CSS framework
- **DaisyUI** - Tailwind CSS component library
- **Alpine.js** - Lightweight JavaScript framework
- **esbuild** - Fast JavaScript bundler

## 🔧 Development

1. **Start the development server**
   ```bash
   php artisan serve
   # or
   php -S localhost:8000 -t public
   ```

2. **Watch for asset changes (in a separate terminal)**
   ```bash
   npm run dev
   ```
   This will automatically rebuild your CSS and JavaScript files whenever you make changes.

3. **Visit** `http://localhost:8000`

## 📧 Default User

After running migrations, you can login with:
- **Email:** test@example.com
- **Password:** password123

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests: `composer test`
5. Submit a pull request

## 📄 License

This project is open source and available under the MIT License.
