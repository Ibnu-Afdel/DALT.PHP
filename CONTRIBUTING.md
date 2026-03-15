# Contributing to DALT.PHP

Thank you for your interest in contributing to DALT.PHP! This document provides guidelines and instructions for contributing.

## 🎯 Ways to Contribute

- **Report bugs** - Found a bug? Open an issue with details
- **Suggest features** - Have an idea? Share it in discussions or issues
- **Improve documentation** - Fix typos, clarify instructions, add examples
- **Add challenges** - Create new debugging challenges for learners
- **Enhance lessons** - Improve existing lesson content
- **Fix bugs** - Submit pull requests for bug fixes
- **Add features** - Implement new functionality

## 🚀 Getting Started

### 1. Fork and Clone

```bash
# Fork the repository on GitHub, then:
git clone https://github.com/YOUR-USERNAME/DALT.PHP.git
cd DALT.PHP
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies for frontend
npm run install-platform

# Setup environment
cp .env.example .env
php artisan migrate
```

### 3. Create a Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/bug-description
```

## 📝 Development Guidelines

### Code Style

- **PHP**: Follow PSR-12 coding standards
- **JavaScript/Vue**: Use consistent formatting (2 spaces indentation)
- **SQL**: Use uppercase for keywords, lowercase for identifiers
- **Comments**: Write clear, helpful comments for complex logic

### Commit Messages

Use clear, descriptive commit messages:

```
feat: Add new routing challenge
fix: Correct middleware execution order
docs: Update installation instructions
test: Add tests for database layer
```

Prefixes:
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `test:` - Test additions or changes
- `refactor:` - Code refactoring
- `style:` - Formatting changes
- `chore:` - Maintenance tasks

### Testing

Before submitting a PR:

```bash
# Run all tests
php artisan test

# Test specific challenge verification
php artisan verify broken-routing

# Test the development server
php artisan serve
```

## 🐛 Reporting Bugs

When reporting bugs, include:

1. **Description** - Clear description of the issue
2. **Steps to reproduce** - Exact steps to trigger the bug
3. **Expected behavior** - What should happen
4. **Actual behavior** - What actually happens
5. **Environment** - PHP version, OS, database type
6. **Error messages** - Full error output if applicable

## ✨ Adding New Challenges

To add a new challenge:

### 1. Create Challenge Structure

```
course/challenges/your-challenge-name/
├── README.md              # Challenge description and hints
├── tests.php             # Verification tests
├── Http/controllers/     # Broken controller code
├── framework/Core/       # Broken framework code (if needed)
└── routes/              # Route definitions (if needed)
```

### 2. Write the Challenge README

```markdown
# Challenge Name

## Learning Objectives
- What students will learn

## The Bug
- Description of what's broken

## Hints
- Hint 1
- Hint 2

## Solution
- Brief explanation (in spoiler tags)
```

### 3. Create Verification Tests

```php
<?php
// tests.php
return [
    [
        'name' => 'Test description',
        'test' => function() {
            // Test logic
            return true; // or false
        },
        'hint' => 'Helpful hint if test fails'
    ],
    // More tests...
];
```

### 4. Test Your Challenge

```bash
php artisan verify your-challenge-name
```

## 📚 Improving Lessons

Lessons are in `course/lessons/` as markdown files:

- Keep explanations clear and beginner-friendly
- Use code examples liberally
- Include practical exercises
- Link to related challenges
- Test all code examples

## 🔍 Pull Request Process

1. **Update documentation** - If you change functionality, update docs
2. **Add tests** - Include tests for new features
3. **Test thoroughly** - Ensure all tests pass
4. **Update CHANGELOG.md** - Add your changes under `[Unreleased]`
5. **Submit PR** - Write a clear description of your changes

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation update
- [ ] New challenge
- [ ] Performance improvement

## Testing
How you tested the changes

## Checklist
- [ ] Code follows project style guidelines
- [ ] Tests pass locally
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
```

## 💬 Communication

- **GitHub Issues** - Bug reports and feature requests
- **GitHub Discussions** - Questions and general discussion
- **Telegram** - Join our community: https://t.me/daltphp

## 📜 Code of Conduct

- Be respectful and inclusive
- Welcome newcomers and help them learn
- Focus on constructive feedback
- Assume good intentions

## 🎓 Learning Resources

If you're new to contributing:

- [How to Contribute to Open Source](https://opensource.guide/how-to-contribute/)
- [GitHub Flow Guide](https://guides.github.com/introduction/flow/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to DALT.PHP! Every contribution helps make learning backend development more accessible. 🚀
