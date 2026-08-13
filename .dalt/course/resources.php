<?php

declare(strict_types=1);

/**
 * Curated external resources — the shippable form of the maintainer's working
 * `docs/hardening/RESOURCES.md` (gitignored, not part of the repository).
 *
 * Selection rules (`DECISIONS.md` → D-06): free, brief, roughly lesson-sized,
 * fetched and confirmed live with the date recorded, and annotated with what
 * to actually read when the canonical page is long.
 *
 * Consumed by `.dalt/Http/controllers/learn/resources.php` (the resources page)
 * and `.dalt/Http/controllers/learn/lesson.php` (each lesson's "Go deeper"
 * links, filtered by the `lessons` key below). Trusted platform content, same
 * shape convention as a challenge `tests.php`: a plain array, `require`d and
 * never exposed to a learner's own files.
 */

return [
    'postgresql' => [
        'title' => 'PostgreSQL',
        'blurb' => 'The largest gap: basic SQL from an MSSQL background, and essentially no Postgres.',
        'links' => [
            [
                'title' => 'PostgreSQL Exercises — Joins & subqueries',
                'url' => 'https://pgexercises.com/questions/joins/',
                'read' => 'All 8 exercises. Interactive, one dataset, answers included.',
                'lessons' => ['10-postgres-intermediate'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => '"Don\'t Do This" wiki',
                'url' => 'https://wiki.postgresql.org/wiki/Don%27t_Do_This',
                'read' => 'The whole page — it\'s short. NOT IN, timestamp without time zone, char(n), money.',
                'lessons' => ['09-postgres-first-contact', '10-postgres-intermediate'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => 'Anatomy of an SQL Index',
                'url' => 'https://use-the-index-luke.com/sql/anatomy',
                'read' => 'This chapter and its three subsections (leaf nodes, B-tree, slow indexes). ~600 words.',
                'lessons' => ['10-postgres-intermediate', '17-observability'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => 'PostgreSQL Tutorial §3.5 — Window Functions',
                'url' => 'https://www.postgresql.org/docs/current/tutorial-window.html',
                'read' => 'The whole section, ~2,000 words. OVER, PARTITION BY, frames, the subquery-to-filter trick.',
                'lessons' => ['13-postgres-advanced'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => 'PostgreSQL §5.9 — Row Security Policies',
                'url' => 'https://www.postgresql.org/docs/current/ddl-rowsecurity.html',
                'read' => 'First two examples only — the page is ~8,000 words. Note the superuser bypass.',
                'lessons' => ['16-postgres-advanced-patterns'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => 'OWASP SQL Injection Prevention',
                'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html',
                'read' => '"Primary Defenses" section only. Gives the allow-list answer for table/column names.',
                'lessons' => ['05-database', '09-postgres-first-contact'],
                'verified' => '2026-08-13',
            ],
        ],
    ],
    'docker' => [
        'title' => 'Docker',
        'blurb' => 'From zero.',
        'links' => [
            [
                'title' => 'Dockerfile best practices',
                'url' => 'https://docs.docker.com/build/building/best-practices/',
                'read' => 'The "General guidelines" half: layer caching, minimal base images, .dockerignore, non-root user, pinning.',
                'lessons' => ['07-dockerfile', '12-docker-intermediate'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => 'Compose file reference — Services',
                'url' => 'https://docs.docker.com/reference/compose-file/services/',
                'read' => 'The healthcheck and depends_on sections only.',
                'lessons' => ['08-docker-compose', '14-docker-production'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => 'docker-curriculum.com',
                'url' => 'https://docker-curriculum.com',
                'read' => 'The "Foundations" section, through the docker run hello-world walkthrough. Free, hands-on, stops before Kubernetes.',
                'lessons' => ['06-docker-basics'],
                'verified' => '2026-08-13',
            ],
        ],
    ],
    'laravel-internals' => [
        'title' => 'Laravel internals',
        'blurb' => 'The side-by-side goal.',
        'links' => [
            [
                'title' => '"The Laravel Core — Demystify The Beast" (Rumpel)',
                'url' => 'https://www.wearedevelopers.com/videos/98/the-laravel-core-demystify-the-beast',
                'read' => 'One free conference talk. Best single-sitting orientation to the container and lifecycle.',
                'lessons' => ['01-request-lifecycle', '03-middleware'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => 'Laravel Core Adventures (Rumpel)',
                'url' => 'https://laravelcoreadventures.com/',
                'read' => 'Free tier episodes only — the pro tier is excluded by D-06.',
                'lessons' => ['01-request-lifecycle', '02-routing', '03-middleware', '04-authentication', '05-database'],
                'verified' => '2026-08-13',
            ],
        ],
    ],
    'testing' => [
        'title' => 'Testing',
        'blurb' => 'For the contract-testing lesson — the repo uses Pest.',
        'links' => [
            [
                'title' => 'Pest — Writing Tests',
                'url' => 'https://pestphp.com/docs/writing-tests',
                'read' => 'The whole page, ~1,800 words. test(), it(), describe(), and the expectation API.',
                'lessons' => ['19-testing-framework-contracts'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => 'Pest — Expectations',
                'url' => 'https://pestphp.com/docs/expectations',
                'read' => 'Skim as reference, don\'t read end to end.',
                'lessons' => ['19-testing-framework-contracts'],
                'verified' => '2026-08-13',
            ],
        ],
    ],
    'security' => [
        'title' => 'Security',
        'blurb' => null,
        'links' => [
            [
                'title' => 'OWASP Password Storage',
                'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html',
                'read' => 'The "Password Hashing Algorithms" section.',
                'lessons' => ['04-authentication'],
                'verified' => '2026-08-13',
            ],
            [
                'title' => 'OWASP Session Management',
                'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html',
                'read' => '"Session ID Life Cycle" only. Explains why identity rotates the session ID before writing identity.',
                'lessons' => ['01-request-lifecycle', '04-authentication'],
                'verified' => '2026-08-13',
            ],
        ],
    ],
];
