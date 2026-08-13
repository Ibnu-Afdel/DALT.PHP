# Challenge: Broken Transaction

**Difficulty:** Medium · **Bugs:** 1 · **Lesson:** [10 — PostgreSQL Core](../../lessons/10-postgres-intermediate/README.md)

## Start

```bash
php artisan challenge:start db-broken-transaction
php artisan migrate
php artisan serve
```

`php artisan challenge:stop` restores everything when you are done.

## Observe the symptom first

`POST /db/transfer` moves credits between two users. It opens a transaction and commits on success, so the happy path works.

Now make the second `UPDATE` fail — send a `to_id` that does not exist, or an amount that violates a constraint:

```bash
curl -i -X POST http://localhost:8000/db/transfer \
  -d "from_id=1&to_id=999999&amount=50"
```

You get an uncaught exception and a 500. Then check the balances:

```bash
docker compose exec db psql -U postgres -d dalt_php_app -c "SELECT id, credits FROM users ORDER BY id;"
```

**The money is still correct.** That is worth sitting with before you read on.

## Why the obvious explanation is wrong

The usual story is "the first UPDATE was committed and the credits vanished". That is what happens with **no transaction at all**, where every statement auto-commits — the case Lesson 10 shows.

It is not what happens here, because `beginTransaction()` was called. Nothing between it and `commit()` is durable, and when the request dies the connection closes with the transaction still open, so the driver discards it. Verify it yourself: the balances are unchanged.

So if the data is safe, what is actually wrong?

## Hints

<details>
<summary>Hint 1 — what you are really fixing</summary>

Three things, none of which is data loss in this particular request:

1. The client gets an uncaught exception instead of a controlled error response.
2. The transaction stays open until the connection tears down, holding row locks that block other writers for longer than necessary.
3. The code depends on cleanup you did not write. Implicit rollback-at-teardown is driver behavior, not a guarantee you control — and it disappears the moment the connection is persistent or pooled, or the moment anything catches the error and keeps running on a poisoned transaction.
</details>

<details>
<summary>Hint 2 — the shape</summary>

Everything between `beginTransaction()` and `commit()` belongs inside a `try`. The `catch` undoes the work explicitly and returns a real response.
</details>

<details>
<summary>Hint 3 — one trap in the catch</summary>

`rollBack()` throws `PDOException: There is no active transaction` when none is open — which is exactly the state you are in if `beginTransaction()` was what failed. Guard it, or your error handler throws a second exception that hides the first.

Catch `\Throwable` rather than `\Exception` so a `TypeError` in the block is handled too.
</details>

## Success criteria

- A successful transfer still returns success and moves the credits.
- A failing transfer returns a controlled error response — `Response::json(['success' => false, ...], 422)`, not an uncaught exception.
- The rollback is explicit, runs before the response is built, and is guarded so it cannot throw on its own.
- Balances are consistent after a failure: a `rollBack()` that never actually runs (a catch block reached but wired to a `finally` that commits anyway, for example) is invisible in the response — the verifier checks the row directly.

## Verify

```bash
php artisan challenge:verify
```

Then confirm the behavior yourself — the checks are a completion signal, not proof.

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 10: PostgreSQL Core** — read this first
- **Next challenge:** db-broken-join
