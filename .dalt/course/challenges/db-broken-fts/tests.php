<?php

/**
 * Broken Full-Text Search — Test Specification
 *
 * Runs against the learner's own PostgreSQL (driver: pgsql, see DECISIONS.md D-09)
 * — a generated tsvector column has no SQLite equivalent, and the whole point is
 * proving Postgres actually stems and ranks the result, not that a keyword sits
 * somewhere in the controller. Each handler_result check opens its own transaction
 * that is always rolled back, so nothing is left behind in the learner's database.
 *
 * uses_search_vector stays a structural check: computing a tsvector on the fly
 * (`to_tsvector(title) @@ ...`) would pass every behavioral check below just as
 * well as using the indexed generated column, but would defeat the GIN index the
 * lesson is actually about — that is invisible in any response, so it needs a
 * check of its own.
 *
 * Verifies:
 *   1. search_vector is referenced, not a tsvector computed on the fly (structural).
 *   2. A search term with a different word ending than the title still matches —
 *      proves stemming, which ILIKE substring matching cannot do.
 *   3. Results are ordered by relevance, not by recency.
 *   4. An empty q still returns 400.
 */

return [
    'uses_search_vector' => [
        'type'   => 'file_contains',
        'file'   => 'Http/controllers/posts/search.php',
        'search' => 'search_vector',
        'hint'   => 'Reference the search_vector column in your WHERE clause — computing a tsvector on the fly would match the same rows but skip the GIN index built for this column.',
    ],

    'stemmed_match_across_word_endings' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/posts/search.php',
        'seed'    => [
            "INSERT INTO posts (title, body, user_id) VALUES ('Docker container basics', 'x', 1)",
        ],
        'query'   => ['q' => 'containers'],
        'expect'  => ['source' => 'body', 'contains' => 'Docker container basics'],
        'hint'    => "ILIKE '%containers%' does not match a title that says \"container\" — the word endings differ. Match search_vector against plainto_tsquery('english', :q) with @@ instead; Postgres stems both to the same root.",
    ],

    'results_ordered_by_relevance_not_recency' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/posts/search.php',
        'seed'    => [
            "INSERT INTO posts (title, body, user_id, created_at) VALUES ('docker docker docker', 'a post that is all about docker', 1, NOW() - INTERVAL '1 day')",
            "INSERT INTO posts (title, body, user_id, created_at) VALUES ('weekly update', 'one passing mention of docker at the end', 1, NOW())",
        ],
        'query'   => ['q' => 'docker'],
        'expect'  => ['source' => 'body', 'before' => 'docker docker docker', 'after' => 'weekly update'],
        'hint'    => "ORDER BY created_at DESC sorts by recency, which is why the weaker, newer match currently comes first. Sort by ts_rank(search_vector, plainto_tsquery('english', :q)) instead.",
    ],

    'empty_query_is_rejected' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/posts/search.php',
        'query'   => ['q' => ''],
        'expect'  => ['status' => 400],
        'hint'    => 'An empty q must still return 400 — this behavior already works and must keep working after the fix.',
    ],
];
