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

A working web application with routing, database access, authentication, and validation already set up. But unlike production frameworks, you can read and understand every line of code.

You write real SQL with prepared statements - no ORM hiding the queries. You see `$_SESSION` arrays directly - no magic session handling. You add CSRF tokens to forms yourself - no automatic protection. This is intentional. You learn by doing it yourself.

The framework includes optional lessons and debugging challenges to help you get started, but they're easily removable. The real learning happens when you build your own projects.

---

## 🚀 Quick Start

```bash
# Create a new project
composer create-project ibnuafdel/daltphp my-project --stability=beta --remove-vcs
cd my-project

# Install Node dependencies
npm install

# Start development
php artisan serve    # Backend: http://localhost:8000
npm run dev          # Frontend: http://localhost:5173
```

Visit `http://localhost:8000` to see your app. Visit `http://localhost:8000/learn` for optional lessons and challenges.

---

## 📚 Learning Features (Optional)

DALT includes 5 lessons and 5 debugging challenges to help you get started:

**Lessons:** Request lifecycle, routing, middleware, authentication, database, sessions

**Challenges:** Fix deliberately broken code in routing, middleware, auth, database, and sessions

Run `php artisan challenge:start broken-routing` to try a challenge. Run `php artisan verify broken-routing` to check your solution.

These are completely optional - you can remove them with `php artisan platform:remove` and just use DALT as a learning framework.

---

## 🛠️ Why PHP for Learning?

PHP is perfect for learning backend development because HTTP concepts are built into the language. You see `$_GET`, `$_POST`, and `$_SESSION` directly instead of framework abstractions. Code runs synchronously (top-to-bottom), making it easier to understand than async languages.

After learning with PHP, these concepts transfer to any backend language. You'll understand what Laravel's Eloquent is doing, what Express.js middleware means, and how authentication works in any framework.

---

## 📖 Documentation

Full documentation at: **[daltphp.com/docs](https://daltphp.com/docs)**

- [What is DALT?](https://daltphp.com/docs/introduction/what-is-dalt) - Understanding the learning framework
- [Why DALT?](https://daltphp.com/docs/introduction/why-dalt) - When DALT is right for you
- [Why PHP?](https://daltphp.com/docs/introduction/why-php) - Why PHP is ideal for learning
- [Quick Start](https://daltphp.com/docs/introduction/quick-start) - Get started in 5 minutes
- [Building a Blog](https://daltphp.com/docs/guides/building-a-blog) - Your first project

---

## 🤝 Contributing

DALT is open source and welcomes contributions. See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

Join the community: [Telegram](https://t.me/daltphp)

---

**Learn backend development by seeing how it actually works** 🔧
