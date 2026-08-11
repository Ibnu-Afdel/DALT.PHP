<div align="center">
  <h1>DALT.PHP</h1>
  <p><strong>A transparent PHP framework for learning backend development</strong></p>

  [![Latest Version](https://img.shields.io/packagist/v/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)
  [![PHP Version](https://img.shields.io/packagist/php-v/ibnuafdel/daltphp.svg?style=flat-square)](https://packagist.org/packages/ibnuafdel/daltphp)
</div>

DALT is a learning framework where you can see and understand everything. The entire codebase is ~1,000 lines of readable PHP. You write real SQL queries, handle security yourself, and see exactly how routing, sessions, and authentication work.

This isn't a framework for production apps. It's a framework for understanding how web applications actually work.

---

## 🎯 What You Get

A working web application with routing, database access, and validation, plus an optional authentication example installed with `php artisan example:install auth`. Unlike production frameworks, you can read and understand every line of code.

The auth installer is repeatable and refuses to overwrite application files or conflicting literal routes. Untouched generated files can later be refreshed with `php artisan example:update auth` or removed with `php artisan example:uninstall auth`; learner modifications are preserved by default.

You write real SQL with prepared statements - no ORM hiding the queries. You see `$_SESSION` arrays directly - no magic session handling. You add CSRF tokens to forms yourself - no automatic protection. This is intentional. You learn by doing it yourself.

The framework includes optional lessons and debugging challenges to help you get started, but they're easily removable. The real learning happens when you build your own projects.

---

## 🚀 Quick Start

```bash
# Create a new project
composer create-project ibnuafdel/daltphp my-project --stability=beta --remove-vcs
cd my-project

# Start the application
php artisan serve    # http://localhost:8000
```

Visit `http://localhost:8000` to see your app. Visit `http://localhost:8000/learn` for optional lessons and challenges.

Production-ready frontend assets are included, so Node.js is not required to start a new project. If you change the learning-platform CSS, JavaScript, or Vue components, run `npm ci` and `npm run dev`; use `npm run build` before distributing those changes.

### Deployment boundary

DALT is an educational framework, not a production-hardened runtime. If you adapt a project for deployment, serve only the `public/` directory, install PHP dependencies with `composer install --no-dev --optimize-autoloader`, set `APP_ENV=production` and `APP_DEBUG=false`, configure HTTPS and secure session cookies, and review [SECURITY.md](SECURITY.md). The built-in `php artisan serve` command is for local development only.

---

## 📚 Learning Features (Optional)

DALT includes 17 lessons and 20 debugging challenges across framework internals, Docker, and PostgreSQL:

**Lessons:** Request lifecycle, routing, middleware, authentication, database, containers, PostgreSQL, reliability, and observability

**Challenges:** Diagnose deliberately broken framework code, container configuration, SQL, migrations, reliability, and database performance

Run `php artisan challenge:start broken-routing` to try a challenge. Run `php artisan challenge:verify` to check your solution.

These are completely optional. Remove them with `php artisan platform:remove` to keep only the framework core. The command preserves application files, including an installed auth example; generated auth files become learner-owned after the platform is gone. Use `--force` only when intentionally running the removal non-interactively.

---

## 🛠️ Why PHP for Learning?

PHP is perfect for learning backend development because HTTP concepts are built into the language. You see `$_GET`, `$_POST`, and `$_SESSION` directly instead of framework abstractions. Code runs synchronously (top-to-bottom), making it easier to understand than async languages.

After learning with PHP, these concepts transfer to any backend language. You'll understand what Laravel's Eloquent is doing, what Express.js middleware means, and how authentication works in any framework.

---

## 📖 Documentation

Full documentation at: **[daltphp.com/docs](https://dalt.ibnuafdel.com/docs)**

- [What is DALT?](https://dalt.ibnuafdel.com/docs/introduction/what-is-dalt) - Understanding the learning framework
- [Why DALT?](https://dalt.ibnuafdel.com/docs/introduction/why-dalt) - When DALT is right for you
- [Why PHP?](https://dalt.ibnuafdel.com/docs/introduction/why-php) - Why PHP is ideal for learning
- [Quick Start](https://dalt.ibnuafdel.com/docs/introduction/quick-start) - Get started in 5 minutes
- [Building a Blog](https://dalt.ibnuafdel.com/docs/guides/building-a-blog) - Your first project

---

## 🤝 Contributing

DALT is open source and welcomes contributions through the [GitHub repository](https://github.com/Ibnu-Afdel/DALT.PHP).

Join the community: [Telegram](https://t.me/daltphp)

---

**Learn backend development by seeing how it actually works** 🔧
