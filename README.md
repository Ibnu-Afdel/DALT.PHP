# DALT.PHP — Lightweight PHP Framework + Guided Learning

[![Latest Version](https://img.shields.io/packagist/v/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)
[![Total Downloads](https://img.shields.io/packagist/dt/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)
[![License](https://img.shields.io/packagist/l/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)
[![PHP Version](https://img.shields.io/packagist/php-v/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)

DALT.PHP is a lightweight PHP framework with an optional built-in guided learning mode.

Build with low abstraction by default, then optionally practice with deliberate broken-code challenges.

## 🎯 What is DALT.PHP?

DALT.PHP combines:
- **Core framework experience** (always available)
- **Guided learning experience** (optional `.dalt` add-on)

Guided learning includes:
- **5 comprehensive lessons** explaining backend fundamentals
- **5 broken challenges** with realistic bugs to fix
- **Automatic verification** with instant feedback
- **Modern stack**: PHP 8+, Vue 3, Tailwind CSS v4, SQLite

## 🚀 Quick Start

### For New Projects (Recommended)

```bash
# Create a new project
composer create-project ibnuafdel/daltphp my-dalt-project --stability=beta --remove-vcs

cd my-dalt-project

# Install dependencies
composer install
npm install

# Start development
php artisan serve    # Backend: http://localhost:8000
npm run dev          # Frontend: http://localhost:5173
```

### For Development/Contributing

```bash
# Clone the repository
git clone https://github.com/Ibnu-Afdel/DALT.PHP.git
cd DALT.PHP

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan migrate

# Start servers
php artisan serve    # Backend: http://localhost:8000
npm run dev          # Frontend: http://localhost:5173
```

## 🎓 Core + Guided Learning

### Core Framework Experience (Default)

Use DALT directly as a micro-framework:

```bash
php artisan serve
```

Build in:
- `routes/routes.php`
- `app/Http/controllers/`
- `resources/views/`

### Guided Learning Experience (Optional)

Visit `/learn` to browse lessons and challenges with the interactive UI, or use the CLI:

```bash
# List available challenges
php artisan challenge:list

# Start a challenge
php artisan challenge:start broken-routing

# Verify your fix
php artisan challenge:verify

# View progress
cat storage/logs/challenges.log
```

Want the framework core only? Remove guided learning:

```bash
# Remove guided learning layer (keeps framework core); same as platform:clean
php artisan platform:remove
```

This command will:
- Remove the `.dalt/` directory (guided learning layer)
- Clean up package.json and composer.json
- Create a minimal README for framework usage
- Keep all framework core functionality intact

The framework core (`framework/Core/`) works independently and will continue to function after removal.

## 📚 Learning Path

1. **Visit `/learn`** - Browse all lessons and challenges
2. **Read a lesson** - Understand the concepts (e.g., routing, middleware)
3. **Try a challenge** - Debug broken code in your editor
4. **Run verification** - Click "Run Verification" or use CLI: `php artisan verify broken-routing`
5. **Get feedback** - See which tests pass/fail with helpful hints
6. **Fix and repeat** - Keep debugging until all tests pass!

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

### CLI Verification
```bash
# Verify a challenge
php artisan verify broken-routing

# Stop challenge and restore clean app
php artisan challenge:stop

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
.dalt/                 # Guided learning layer (optional; UI & course)
  course/              # Lessons + challenges
    lessons/           # 5 lesson markdown files
    challenges/        # 5 broken challenge folders
      broken-routing/
      broken-middleware/
      broken-auth/
      broken-database/
      broken-session/
  Http/controllers/   # /learn and related controllers
  resources/           # Guided-learning views & assets
  routes/              # Guided-learning routes
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
