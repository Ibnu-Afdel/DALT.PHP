# Changelog

All notable changes to DALT.PHP will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **MySQL support** — `DB_DRIVER=mysql` is now a first-class supported driver (via
  `pdo_mysql`), mirroring how PostgreSQL is handled: hardened connection validation,
  DSN construction with charset, `migrate:fresh` and `make:migration` MySQL branches,
  and automatic SQLite-to-MySQL conversion of migration files. The `DB_DRIVER` contract
  now accepts `sqlite`, `pgsql`, and `mysql`.
- **MySQL stacked-query protection** — MySQL connections disable multi-statement
  execution (`PDO::MYSQL_ATTR_MULTI_STATEMENTS` / `Pdo\Mysql::ATTR_MULTI_STATEMENTS`)
  so a single injected statement cannot be amplified into stacked destructive ones,
  matching the single-statement posture of PostgreSQL. The migration runner splits
  raw migration files into individual statements to run under this hardened driver.

### Changed

- Replaced the installed-project marketing page with a focused welcome screen that
  links to learning and shows how to remove the optional platform.

## [1.0.0] - 2026-08-24

The first stable release. DALT is still an educational framework and says so
everywhere — what changes at 1.0.0 is that the public surface now carries a written,
tested compatibility promise instead of a beta disclaimer.

### Added

- **[COMPATIBILITY.md](COMPATIBILITY.md)** — the v1 contract. It records the public
  `Core` classes and methods, the global helpers, routing and response behavior,
  configuration and environment keys, the Artisan command set, migration behavior, the
  auth-example lifecycle, and the `platform:remove` guarantee, together with an explicit
  list of what is *not* covered. `tests/Feature/CompatibilityContractTest.php` checks the
  document against the code in both directions, so the promise cannot silently drift.
- **[CONTRIBUTING.md](CONTRIBUTING.md)** — local setup, the commands that must pass, the
  framework/course boundary, how course content is reviewed, and where security reports
  go instead of the issue tracker.
- **[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)** — Contributor Covenant 2.1.
- **Continuous integration** — a PHP 8.2/8.3/8.4 matrix, frontend jobs for both the root
  and `.dalt` toolchains, a PostgreSQL job, a Docker job, a Composer-artifact install
  smoke test, the destructive skeleton-isolation proof, dependency auditing, and secret
  scanning. Integration jobs fail on an unexpected skip rather than reporting green.
- **Issue and pull-request templates** that ask for reproduction, environment, expected
  behavior, and actual behavior, and route security reports privately.
- **DALT Fullstack** — 60 concise theory lessons across 13 parts taking React,
  TypeScript, and Tailwind down through HTTP and JSON into PHP and PostgreSQL under
  Docker, with 19 build milestones.
- **DALT Build** — 71 guided lessons that build one issue-tracking application from an
  empty skeleton, in the order real product development creates the need for each idea.
- **[Sources and Attribution](documentation/sources-and-attribution.md)** — the public
  curriculum credit, licensing boundary, research period, and official technical-source
  index behind the learning material.
- **Router**: `OPTIONS` support, and prefix fallback routes (`/app/{*}`) that match a
  prefix and everything beneath it across segment boundaries — what an SPA route table
  and an API preflight catch-all both need.
- **Frontend test tooling** in the root toolchain: Vitest, Testing Library, and jsdom.

### Changed

- Installation no longer requires `--stability=beta`. The documented command is
  `composer create-project ibnuafdel/daltphp my-project --remove-vcs`.
- PHP 8.5 is supported. The `^8.2` constraint already permitted it, so the documented
  support window now matches what Composer will actually install.
- `minimum-stability` is `stable`; no dependency requires a pre-release.
- README describes the three learning surfaces separately — Core, Fullstack theory, and
  DALT Build — and states the framework size, supported PHP versions, extensions,
  databases, and operating-system boundary. The previous "17 lessons and 20 challenges"
  and "~1,000 lines" claims were both stale.
- `LEARNING_MATERIALS.md` is now a public guide to Core, Fullstack theory, and DALT
  Build instead of an obsolete two-track planning handoff. Maintainer source records
  point to public source information rather than excluded internal worklogs.
- `SECURITY.md` supports the `1.x` line, states what a security fix is allowed to break,
  and points at GitHub Security Advisories for published vulnerabilities.
- Challenge verification checks behavior rather than matching learner source text.

### Fixed

- `.env.example` advertised a MySQL configuration block. `Database` accepts only
  `sqlite` and `pgsql`, and rejects anything else with
  `InvalidArgumentException: Unsupported database driver`. The false block is gone.
- Hosted documentation links pointed at a `/docs/introduction/*` and `/docs/guides/*`
  structure the site no longer serves; every one returned 404. README and both footers
  now point at pages that resolve. All 490 external links in the tracked tree were
  checked, and the one broken third-party link in the competency roadmap was corrected.
- The README hosted-docs label read `daltphp.com/docs`, a domain that does not exist.
- Build milestone B03 stage 1 had no "Check it yourself" section, so the first stage of
  the first build milestone could be "finished" without the learner knowing whether the
  Vite entry point had actually moved.
- Trailing whitespace across 25 tracked files; two Markdown hard breaks that depended on
  invisible trailing spaces are now explicit lists.
- Every lesson, build and challenge page rendered two `<h1>` elements: one from the
  layout header and one from the Markdown source, which also opens with the title. On
  guided Build pages the two were word-for-word identical, so a screen reader announced
  the same top-level heading twice in a row. Content headings now start at `<h2>`, and a
  leading heading that only repeats the page title is dropped. The demotion runs on the
  parsed document, not the source text, so a `# comment` inside a fenced shell block is
  untouched — the Docker lesson alone has thirty-two of those.
- `Router::only()` raised "Using null as an array offset is deprecated" on PHP 8.5 when
  called before any route was registered, on its way to throwing the intended
  `LogicException`.
- **`composer.lock` could not be installed on PHP 8.2 or 8.3.** Regenerating it on a
  machine running 8.4 resolved Symfony 8.1.x, which requires PHP >= 8.4.1, so
  `composer create-project` failed outright on two of the four supported versions with
  "Your lock file does not contain a compatible set of packages". `config.platform.php`
  is now pinned to `8.2.0`, so the lockfile always resolves for the lowest version the
  package claims to support rather than for whoever last ran `composer update`.

### Removed

- `php artisan fullstack:status`. It was an authoring command that read `docs/` files
  excluded from every distribution, and it reported a superseded lesson count. Removing
  it before 1.0.0 keeps it out of the CLI compatibility promise.
- The empty tracked `.codex` file.
- `lucide-vue-next`, `reka-ui`, and `shadcn-vue` from the `.dalt` toolchain. None was
  imported by any source file; removing them takes that install from 455 packages to 86
  and produces byte-identical build output.

### Security

- Development dependency remediation: Vitest to 4.1.11 (fixes a critical arbitrary
  file read and execute through the Vitest UI server, GHSA-5xrq-8626-4rwp) and Vite to
  8.2.2 (fixes a `server.fs.deny` bypass, GHSA-fx2h-pf6j-xcff, and an NTLMv2 hash
  disclosure through `launch-editor`, GHSA-v6wh-96g9-6wx3). Production dependencies were
  never affected, but contributor and CI tooling is part of the public release. Both
  `npm audit` runs and both Composer audits now report zero advisories.
- ESLint moved to 10.9.0; the 9.x line is no longer supported upstream.

### Upgrade notes

There is no in-place upgrade from a `0.x` beta. The betas carried no compatibility
promise and the learning platform changed substantially. Start a new project with
`composer create-project ibnuafdel/daltphp my-project --remove-vcs` and copy your
application code from `app/`, `routes/`, `database/migrations/`, and `resources/` into
it. Nothing under `framework/` or `.dalt/` should be carried across.

If you removed the learning platform with `platform:remove`, that remains the supported
way to keep only the framework, and it is now proven in CI on every commit.

## [0.4.1-beta] - 2026-08-13

### Fixed
- Installation shipped without prebuilt `.dalt` assets, and `.dalt` dependencies were
  not installed automatically

## [0.4.0-beta] - 2026-08-13

### Added
- Docker, PostgreSQL, database-layer and observability challenges converted to
  executable verification, plus `compose_config`, `handler_result` and `class_contract`
  check types
- Framework reference, quick start, architecture, debugging and contributor
  documentation, and an interactive competency roadmap
- Laravel bridges and curated external reading across the course

### Changed
- DALT's asset build and dependencies isolated into `.dalt/`, with the root Vite
  pipeline serving the application
- Learning shell, roadmap, challenge pages and Markdown rendering redesigned;
  server-side Markdown rendering replaced the client pipeline
- DALT-only tests moved under `.dalt/tests` with isolation coverage

### Fixed
- Challenge fixtures that could not run, unrunnable Docker and PostgreSQL lesson
  examples, and a dead-code false positive in the verifier

## [0.3.0-beta.3] - 2026-05-11

### Added
- Prebuilt Vue and Tailwind assets so a new PHP project runs without Node.js

### Changed
- Updated the frontend toolchain and dependency constraints

## [0.3.0-beta.2] - 2026-05-11

### Added
- Docker, PostgreSQL, database-layer, and observability learning modules and challenges

## [0.3.0-beta.1] - 2026-03-29

### Changed
- Cleaned up distribution structure for end users
- Removed `CONTRIBUTING.md` and `TESTING_GUIDE.md` from distribution (dev-only files)
- Removed `composer.lock` and `package-lock.json` from tracking (users generate their own)
- `LICENSE` and `SECURITY.md` kept in repo but excluded from distribution archives
- Updated GitHub link to correct URL: `https://github.com/ibnu-Afdel/dALT.PHP`

### Fixed
- Users now get a clean project structure without unnecessary dev files

## [0.2.0-beta.7] - 2026-03-19

### Added
- `php artisan platform:remove` command to cleanly convert to standalone framework
- Automated cleanup script that removes learning platform
- `.dalt/bootstrap.php` for proper autoloading of platform classes
- Proper decoupling between framework core and learning platform

### Changed
- **BREAKING**: Moved learning-specific classes (ChallengeManager, CourseLoader, ChallengeVerifier) from `framework/Core/` to `.dalt/Core/`
- Fixed Vite configuration to work from project root instead of `.dalt/` directory
- Updated view resolution priority: app views first, then .dalt views (was reversed)
- Updated route loading priority: user routes first, then platform routes
- Router now checks if `.dalt/` exists before falling back to platform controllers
- Updated README to accurately reflect framework capabilities and removal process

### Fixed
- Vite styling not working due to incorrect root configuration
- Node modules path resolution issues
- Framework coupling to `.dalt/` directory (now properly optional)
- Misleading documentation about "no coupling" - now honest about dependencies

### Removed
- False claims about framework independence (replaced with honest documentation)
- Hardcoded `.dalt/` dependencies from core framework files

## [0.1.0-beta.2] - 2026-03-15

### Added
- `challenge:list` - List available challenges with difficulty and pass status
- `challenge:start <name>` - Load broken files into app (with confirmation)
- `challenge:stop` - Restore clean app, remove challenge files
- meta.json per challenge/lesson for scalable content (add new without touching PHP)
- CourseLoader and ChallengeManager for discovery and lifecycle
- Standalone welcome in `resources/views/` for app without .dalt

### Changed
- Moved `course/` into `.dalt/course/` (lessons + challenges)
- Docs and view templates: `verify` → `challenge:start` + `challenge:verify` workflow
- View priority: .dalt first if exists, else app (standalone mode)
- Web verification requires active challenge (fixes false pass on unloaded challenges)
- ChallengeVerifier supports verify-against-base mode for real app checking

### Fixed
- Web "Run Verification" falsely passing when challenge not loaded

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

[Unreleased]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v0.4.1-beta...v1.0.0
[0.4.1-beta]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v0.4.0-beta...v0.4.1-beta
[0.4.0-beta]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v0.3.0-beta.3...v0.4.0-beta
[0.3.0-beta.3]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v0.3.0-beta.2...v0.3.0-beta.3
[0.3.0-beta.2]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v0.3.0-beta.1...v0.3.0-beta.2
[0.3.0-beta.1]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v0.2.0-beta.7...v0.3.0-beta.1
[0.2.0-beta.7]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v0.1.0-beta.2...v0.2.0-beta.7
[0.1.0-beta.2]: https://github.com/Ibnu-Afdel/DALT.PHP/compare/v0.1.0-beta.1...v0.1.0-beta.2
[0.1.0-beta.1]: https://github.com/Ibnu-Afdel/DALT.PHP/releases/tag/v0.1.0-beta.1
[0.1.0-alpha.3]: https://github.com/Ibnu-Afdel/DALT.PHP/releases/tag/v0.1.0-alpha.3
