# DALT.PHP — Interactive Backend Debugging Playground

[![Latest Version](https://img.shields.io/packagist/v/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)
[![Total Downloads](https://img.shields.io/packagist/dt/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)
[![License](https://img.shields.io/packagist/l/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)
[![PHP Version](https://img.shields.io/packagist/php-v/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)

An educational platform where you learn backend development by debugging intentionally broken code.

**Learn by fixing real bugs** in routing, middleware, authentication, database, and session handling.

## 🎯 What is DALT.PHP?

DALT.PHP is an interactive debugging playground that teaches web framework concepts through hands-on practice:
- **5 comprehensive lessons** explaining backend fundamentals
- **5 broken challenges** with realistic bugs to fix
- **Automatic verification** with instant feedback
- **Modern stack**: PHP 8+, Vue 3, Tailwind CSS v4, SQLite

## 🚀 Quick Start

### For New Projects (Recommended)

```bash
# Create a new project
composer create-project ibnuafdel/daltphp my-dalt-project
cd my-dalt-project

# Install frontend dependencies
npm run install-platform

# Start development
php artisan serve    # Visit http://localhost:8000
```

### For Development/Contributing

```bash
# Clone the repository
git clone https://github.com/Ibnu-Afdel/DALT.PHP.git
cd DALT.PHP

# Install dependencies
composer install
npm run install-platform

# Setup environment
cp .env.example .env
php artisan migrate

# Start servers
php artisan serve    # Visit http://localhost:8000
```

## 📚 Learning Path

1. **List challenges** - `php artisan challenge:list` to see what's available
2. **Start a challenge** - `php artisan challenge:start broken-routing` loads broken code into your app
3. **Fix the bugs** - Edit the real files in your IDE (`routes/`, `framework/`, `app/Http/controllers/`)
4. **Verify** - `php artisan challenge:verify` checks your fix
5. **Get feedback** - See which tests pass/fail with helpful hints
6. **Reset if stuck** - `php artisan challenge:reset` restores the buggy baseline

Or use the web UI: visit `/learn`, read lessons, start challenges, and run verification from the browser.

## 🐛 Available Challenges

| Challenge | Difficulty | Bugs | Concept |
|-----------|-----------|------|---------|
| Broken Routing | Easy | 2 | Route order and registration |
| Broken Middleware | Medium | 2 | Auth checks and CSRF validation |
| Broken Authentication | Easy | 1 | Password verification |
| Broken Database | Medium | 2 | SQL injection prevention |
| Broken Session | Medium | 2 | Flash data handling |

## 🎓 Features

### Interactive Web Interface
- Browse lessons and challenges at `/learn`
- Read beautifully formatted markdown lessons
- Run verifications directly from the browser
- Get instant visual feedback with test results

### Challenge CLI
```bash
# List available challenges
php artisan challenge:list

# Start a challenge (copies broken files into your app)
php artisan challenge:start broken-routing

# Verify your fix (checks the real app)
php artisan challenge:verify

# Reset to buggy baseline if you get stuck
php artisan challenge:reset

# View progress logs
cat storage/logs/challenges.log
```

### Automatic Testing
- 19 automated tests across all challenges
- Detailed pass/fail results
- Helpful hints for failures
- Progress tracking

## 📁 Project Structure

```
.dalt/                 # Platform internals (learning UI & assets)
  course/              # Learning content (lessons + challenges)
    lessons/           # 5 lesson markdown files
    challenges/        # 5 broken challenge folders
      broken-routing/
      broken-middleware/
      broken-auth/
      broken-database/
      broken-session/
  Http/controllers/   # Learning UI controllers
  resources/           # Platform assets & views
  routes/              # Platform routes
  scripts/             # Setup scripts
  stubs/               # Code templates
app/Http/controllers/  # Your controllers (start building here)
config/                # Configuration files
database/              # SQLite database and migrations
framework/Core/        # Framework core (Router, DB, Session, etc.)
public/                # Web root (index.php entry point)
routes/routes.php      # Your route definitions
storage/               # Logs and runtime files
tests/                 # Testing suite
```

**Note**: When you install via Composer, development-only files (like internal docs) are automatically excluded.

## 🛠️ Tech Stack

- **Backend**: PHP 8+ with custom micro-framework
- **Frontend**: Vue 3 + Tailwind CSS v4 + Vite
- **Database**: SQLite (Postgres/MySQL ready)
- **Testing**: Custom verification system

## 📖 Documentation

- [Testing Guide](TESTING_GUIDE.md) - Complete testing instructions
- [Framework Architecture](docs/FRAMEWORK_ARCHITECTURE.md) - How the framework works
- [Project Architecture](docs/PROJECT_ARCHITECTURE.md) - Overall system design
- [Verification System](docs/VERIFICATION_SYSTEM.md) - How verification works
- [Milestone Summaries](docs/) - Development progress

## 🎯 Learning Objectives

After completing DALT.PHP, you'll understand:
- HTTP request lifecycle in web frameworks
- Routing and URL-to-controller mapping
- Middleware and request filtering
- Authentication and password security
- Database queries and SQL injection prevention
- Session management and flash data
- Common backend bugs and how to fix them

## 🤝 Contributing

We welcome contributions! Whether you want to:
- Add new challenges or lessons
- Improve documentation
- Fix bugs or add features
- Enhance the UI

Please read our [Contributing Guide](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 🔒 Security

Found a security vulnerability? Please review our [Security Policy](SECURITY.md) for responsible disclosure guidelines.

## 📝 License

MIT License - see the [LICENSE](LICENSE) file for details.

## 📖 Documentation

- [CHANGELOG](CHANGELOG.md) - Version history and changes
- [CONTRIBUTING](CONTRIBUTING.md) - How to contribute
- [SECURITY](SECURITY.md) - Security policy and reporting
- [TESTING_GUIDE](TESTING_GUIDE.md) - Complete testing instructions

## 🔗 Links

- [GitHub](https://github.com/Ibnu-Afdel/DALT.PHP)
- [Telegram Community](https://t.me/daltphp)

---

**Start learning backend development the practical way** - by debugging real code! 🐛🔧
