# Challenge: Broken Authentication

**Difficulty:** Easy · **Bugs:** 1 · **Lesson:** [04 — Authentication](../../lessons/04-authentication/README.md)

## Start

```bash
php artisan challenge:start broken-auth
php artisan migrate
php artisan serve
```

`migrate` creates the `users` table this challenge needs. When you are done, `php artisan challenge:stop` restores everything — never copy or delete these files by hand.

## Observe the symptom first

1. Register a new account at `/auth/register`. It succeeds.
2. Log in at `/auth/login` with the exact credentials you just used. It is rejected.
3. Register a second account and try again. Rejected again.

Registration works, the row is in the database, and the password is right — yet no account can ever log in. One account failing suggests a typo; *every* account failing points at the verification step itself.

## Hints

Work down this list only as far as you need.

<details>
<summary>Hint 1 — where to look</summary>

The defect is in `framework/Core/Authenticator.php`. Registration writes the row correctly; find the code that decides whether a submitted password matches the stored one.
</details>

<details>
<summary>Hint 2 — see the two values</summary>

Print the submitted password next to the value stored in the `password` column. They are both strings, but they are not the same *kind* of string. What did registration do to the password before storing it?
</details>

<details>
<summary>Hint 3 — the concept</summary>

A password hash is deliberately one-way, and a modern hash embeds a random salt, so hashing the same password twice produces two different strings. That rules out comparing the values directly *and* rules out re-hashing the candidate and comparing the results. PHP provides one function that reads the salt and cost out of the stored hash and does the comparison correctly — in constant time, so response timing does not leak how much matched.
</details>

## Success criteria

- An account registered through the form can log in with its own password.
- A wrong password is still rejected.
- A password that is correct except for case or whitespace is still rejected.
- Verification is timing-safe.

## Verify

```bash
php artisan challenge:verify
```

Then confirm the behavior yourself — the checks are a completion signal, not proof. Register an account, log in with the correct password, then log out and retry with a wrong one.

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 04: Authentication** — read this first
- **Next challenge:** Broken Database
