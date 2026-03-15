# Changelog

All notable changes to DALT.PHP will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0-beta.1] - 2026-03-15

### Added
- Complete interactive learning platform with web UI at `/learn`
- 5 comprehensive lessons covering backend fundamentals
- 5 debugging challenges with automatic verification
- Vue 3 + Tailwind CSS v4 frontend with Vite
- CLI verification system via `php artisan verify`
- Browser-based verification with instant feedback
- Progress tracking and logging system
- Authentication example with `php artisan example:install auth`
- Comprehensive artisan CLI with help system
- PostgreSQL and MySQL support alongside SQLite
- Migration system with `php artisan migrate`
- Post-create-project setup script
- Complete documentation and testing guide

### Changed
- Upgraded to beta status with stable API
- Improved error messages and hints in verification system
- Enhanced README with clear learning path
- Standardized on SQLite as default database (with PostgreSQL/MySQL support)
- Default development server port: 8000

### Fixed
- Route registration order in broken-routing challenge
- Middleware execution flow in broken-middleware challenge
- Password verification in broken-auth challenge
- SQL injection prevention in broken-database challenge
- Flash data handling in broken-session challenge

## [0.1.0-alpha.3] - 2024

### Added
- Initial alpha release
- Basic framework structure
- Core routing and middleware system
- Database abstraction layer
- Session management
- Initial challenge set

---

## Version History

- **0.1.0-beta.1** - First beta release with complete learning platform
- **0.1.0-alpha.3** - Last alpha release
- **0.1.0-alpha.2** - Early alpha
- **0.1.0-alpha.1** - Initial alpha release

[Unreleased]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v0.1.0-beta.1...HEAD
[0.1.0-beta.1]: https://github.com/Ibnu-Afdel/DALT.PHP/releases/tag/v0.1.0-beta.1
[0.1.0-alpha.3]: https://github.com/Ibnu-Afdel/DALT.PHP/releases/tag/v0.1.0-alpha.3
