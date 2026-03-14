# DALT.PHP — Interactive Backend Debugging Playground

An educational platform where you learn backend development by debugging intentionally broken code.

**Learn by fixing real bugs** in routing, middleware, authentication, database, and session handling.

## 🎯 What is DALT.PHP?

DALT.PHP is an interactive debugging playground that teaches web framework concepts through hands-on practice:
- **5 comprehensive lessons** explaining backend fundamentals
- **5 broken challenges** with realistic bugs to fix
- **Automatic verification** with instant feedback
- **Modern stack**: PHP 8+, Vue 3, Tailwind CSS v4, SQLite

## 🚀 Quick Start

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

# Start servers (2 terminals)
npm run dev          # Terminal 1: Vite dev server
php artisan serve    # Terminal 2: PHP server

# Visit the app
open http://localhost:8888
```

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
Http/controllers/       # Plain PHP controllers
  learn/               # Learning interface controllers
  api/                 # Verification API
routes/routes.php      # Route definitions
resources/
  views/               # PHP views
    learn/             # Learning interface views
  js/
    app.js             # Vue 3 entry point
    components/        # Vue components (LessonContent, ChallengeVerifier)
  css/input.css        # Tailwind CSS v4
framework/Core/        # Framework core (Router, DB, Session, etc.)
lessons/               # 5 lesson markdown files
challenges/            # 5 broken challenge folders
  broken-routing/
  broken-middleware/
  broken-auth/
  broken-database/
  broken-session/
database/              # SQLite database and migrations
docs/                  # Architecture and milestone documentation
```

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

Contributions are welcome! You can:
- Add new challenges
- Improve lessons
- Enhance the UI
- Fix bugs
- Add features

## 📝 License

MIT

## 🔗 Links

- [GitHub](https://github.com/Ibnu-Afdel/DALT.PHP)
- [Telegram Community](https://t.me/daltphp)

---

**Start learning backend development the practical way** - by debugging real code! 🐛🔧
