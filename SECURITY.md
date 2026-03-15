# Security Policy

## Supported Versions

We release patches for security vulnerabilities in the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 0.1.x   | :white_check_mark: |
| < 0.1   | :x:                |

## Reporting a Vulnerability

We take security seriously. If you discover a security vulnerability in DALT.PHP, please report it responsibly.

### How to Report

**Please do NOT report security vulnerabilities through public GitHub issues.**

Instead, please report them via:

1. **Email**: ibnuafdel@gmail.com
2. **Subject**: `[SECURITY] Brief description of vulnerability`

### What to Include

Please include the following information:

- **Type of vulnerability** (e.g., SQL injection, XSS, authentication bypass)
- **Location** - File path and line number if possible
- **Step-by-step instructions** to reproduce the issue
- **Proof of concept** or exploit code (if available)
- **Impact** - What an attacker could achieve
- **Suggested fix** (if you have one)

### Response Timeline

- **Initial response**: Within 48 hours
- **Status update**: Within 7 days
- **Fix timeline**: Depends on severity
  - Critical: 1-7 days
  - High: 7-14 days
  - Medium: 14-30 days
  - Low: 30-90 days

### What to Expect

1. We'll acknowledge receipt of your report
2. We'll investigate and validate the vulnerability
3. We'll develop and test a fix
4. We'll release a security patch
5. We'll publicly disclose the vulnerability (with credit to you, if desired)

## Security Best Practices for Users

When using DALT.PHP in production:

### 1. Environment Configuration

```bash
# .env file
APP_ENV=production
APP_DEBUG=false
```

### 2. Database Security

- Use prepared statements (DALT.PHP does this by default)
- Never expose database credentials
- Use strong database passwords
- Limit database user permissions

### 3. Authentication

- Use strong password hashing (DALT.PHP uses `password_hash()`)
- Implement rate limiting on login attempts
- Use HTTPS in production
- Set secure session cookies

### 4. Input Validation

- Validate all user input
- Sanitize output to prevent XSS
- Use CSRF protection (enabled by default in DALT.PHP)

### 5. File Permissions

```bash
# Secure permissions
chmod 755 public/
chmod 644 .env
chmod 755 storage/
```

### 6. Keep Dependencies Updated

```bash
composer update
npm update
```

## Known Security Considerations

### Educational Purpose

DALT.PHP is designed as a **learning platform** with intentionally broken code in challenges. The challenges demonstrate common security vulnerabilities for educational purposes.

**Important**: 
- Challenge code is isolated and not used in the main application
- The framework itself implements security best practices
- Always review and understand code before using in production

### Production Use

While DALT.PHP can be used as a foundation for real projects:

1. **Remove challenge code** - Delete `.dalt/` (or use without it) for production
2. **Review all code** - Audit before deploying
3. **Add additional security layers** - Rate limiting, WAF, etc.
4. **Follow security best practices** - See above
5. **Keep updated** - Watch for security releases

## Security Features

DALT.PHP includes:

- ✅ Prepared statements for SQL queries
- ✅ Password hashing with `password_hash()`
- ✅ CSRF protection middleware
- ✅ Session security with httponly cookies
- ✅ Input validation helpers
- ✅ XSS prevention in views

## Disclosure Policy

- We follow **responsible disclosure** practices
- Security fixes are released as soon as possible
- We credit researchers who report vulnerabilities (unless they prefer anonymity)
- We maintain a security advisory page for disclosed vulnerabilities

## Contact

For security concerns: **ibnuafdel@gmail.com**

For general questions: Open a GitHub issue or join our [Telegram community](https://t.me/daltphp)

---

Thank you for helping keep DALT.PHP and its users safe! 🔒
