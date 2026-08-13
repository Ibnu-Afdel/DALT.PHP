# Challenge: Untested Contract

**Difficulty:** Medium · **Bugs:** 1 · **Lesson:** [19 — Testing a Framework Contract](../../lessons/19-testing-framework-contracts/README.md)

## Start

```bash
php artisan challenge:start untested-contract
php artisan migrate
php artisan serve
```

`php artisan challenge:stop` restores everything when you are done.

## The contract, stated up front

`POST /coupons/redeem` takes a `code`. It promises:

1. An unknown code is rejected — 404, `{"redeemed": false, ...}`.
2. A valid, unused code is redeemed — 200, `{"redeemed": true}`.
3. **A code that has already been redeemed is rejected, not redeemed a second time.**

## Observe the symptom first

You will need a coupon in the database. Insert one directly:

```bash
sqlite3 database/app.sqlite "CREATE TABLE IF NOT EXISTS coupons (code TEXT PRIMARY KEY, times_redeemed INTEGER NOT NULL DEFAULT 0); INSERT OR IGNORE INTO coupons VALUES ('SUMMER10', 0);"
```

Now redeem it:

```bash
curl -i -X POST http://localhost:8000/coupons/redeem -d "code=SUMMER10"
```

`{"redeemed":true}`. That is correct — this is contract #2, and it is the only thing a single manual try can ever check. Now do the exact same request again:

```bash
curl -i -X POST http://localhost:8000/coupons/redeem -d "code=SUMMER10"
```

**`{"redeemed":true}` again.** The same coupon was just redeemed twice. If this were a real discount code, a percentage off a subscription, or a single-use invite, this is the bug that costs money or breaks an invariant someone assumed was obvious.

## Why "I tried it and it worked" missed this

Contract #3 only becomes observable on the *second* call against the *same* code. A manual smoke test — load the page, submit the form, see success — almost always stops after the first. This is precisely why [Lesson 19](../../lessons/19-testing-framework-contracts/README.md) exists: a test that seeds the database as if a previous request already happened proves the second-call case without a human remembering to try it twice, every time, forever.

## Hints

<details>
<summary>Hint 1 — where the check belongs</summary>

The controller already looks the coupon up before deciding what to do. The fix is a condition on the value it already has — you do not need a second query.
</details>

<details>
<summary>Hint 2 — the shape</summary>

```php
if ($coupon === false) {
    return Response::json(['redeemed' => false, 'error' => 'Unknown coupon code.'], 404);
}

// new: reject an already-redeemed coupon here, before the UPDATE runs

$db->query('UPDATE coupons SET times_redeemed = times_redeemed + 1 WHERE code = :code', ['code' => $code]);

return Response::json(['redeemed' => true]);
```
</details>

<details>
<summary>Hint 3 — the status code</summary>

404 means the coupon does not exist. This is a different failure — the coupon exists but is no longer usable. 409 (Conflict) is the accurate status for "this request conflicts with the current state of the resource."
</details>

## Success criteria

- An unknown code still returns 404.
- A fresh, unredeemed code still returns 200 and `{"redeemed": true}`.
- A code seeded as already redeemed returns 409 and `{"redeemed": false, ...}` — **without** running the `UPDATE` again.

## Verify

```bash
php artisan challenge:verify
```

One of the checks seeds the database directly in the "already redeemed" state and makes exactly one request — reproducing the second-call bug without needing your own two-request repro. Confirm the behavior yourself with `curl` too, calling the endpoint twice against the same seeded coupon; the verifier is a completion signal, not a substitute for watching it happen.

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 19: Testing a Framework Contract** — read this first
- **Transfer exercise:** write a Pest test for this exact contract from a blank file, per the lesson's transfer exercise, before moving on
