<?php

declare(strict_types=1);

$errors = Core\Session::get('errors', []);
$errors = is_array($errors) ? $errors : [];
$name = old('name');
$email = old('email');
$name = is_string($name) ? $name : '';
$email = is_string($email) ? $email : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create account · DALT.PHP</title>
  <style>
    :root { color-scheme: dark; font-family: ui-sans-serif, system-ui, sans-serif; background: #080a0d; color: #f4f7f5; }
    * { box-sizing: border-box; }
    body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 1.5rem; background: radial-gradient(circle at 50% 0%, #17231c 0, #080a0d 38rem); }
    main { width: min(100%, 27rem); padding-block: 2rem; }
    a { color: #a9e7b0; text-underline-offset: .2em; }
    h1 { margin: 0 0 .5rem; font-size: clamp(2rem, 7vw, 3.25rem); letter-spacing: -.04em; line-height: 1; }
    .intro { margin: 0 0 2rem; color: #a9b3ad; }
    .notice { margin-block-end: 1rem; padding: .8rem 1rem; border-radius: .75rem; background: #301719; color: #ffc7ca; overflow-wrap: anywhere; }
    form { display: grid; gap: 1.05rem; }
    label { display: grid; gap: .45rem; font-weight: 650; }
    input { width: 100%; min-height: 3rem; border: 1px solid #3a433e; border-radius: .75rem; padding: .75rem .85rem; background: #111511; color: inherit; font: inherit; }
    input:focus-visible { outline: 3px solid #93da97; outline-offset: 2px; }
    .hint, .field-error { margin: -.2rem 0 0; font-size: .9rem; font-weight: 450; overflow-wrap: anywhere; }
    .hint { color: #a9b3ad; }
    .field-error { color: #ffc7ca; }
    button { min-height: 3rem; border: 0; border-radius: .75rem; padding: .75rem 1rem; background: #93da97; color: #071009; font: inherit; font-weight: 750; cursor: pointer; }
    button:hover { background: #b0edb5; }
    button:focus-visible, a:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
    .alternate { margin: 1.5rem 0 0; color: #a9b3ad; }
    @media (prefers-reduced-motion: no-preference) { button { transition: background-color 160ms ease-out, transform 160ms ease-out; } button:hover { transform: translateY(-1px); } }
  </style>
</head>
<body>
  <main>
    <h1>Create your account.</h1>
    <p class="intro">Build a real session-backed authentication flow you can inspect end to end.</p>

    <?php if ($errors !== []): ?>
      <p class="notice" role="alert">Your account wasn’t created. Check the fields below.</p>
    <?php endif; ?>

    <form method="POST" action="/register">
      <?= csrf_field() ?>
      <label for="name">
        Name
        <input id="name" name="name" type="text" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" maxlength="255" autocomplete="name" required aria-describedby="name-error">
      </label>
      <?php if (is_string($errors['name'] ?? null)): ?><p class="field-error" id="name-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

      <label for="email">
        Email
        <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" maxlength="254" autocomplete="email" required aria-describedby="email-error">
      </label>
      <?php if (is_string($errors['email'] ?? null)): ?><p class="field-error" id="email-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

      <label for="password">
        Password
        <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="password-hint password-error">
      </label>
      <p class="hint" id="password-hint">Use 8–72 characters.</p>
      <?php if (is_string($errors['password'] ?? null)): ?><p class="field-error" id="password-error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

      <label for="password-confirmation">
        Confirm password
        <input id="password-confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="confirmation-error">
      </label>
      <?php if (is_string($errors['password_confirmation'] ?? null)): ?><p class="field-error" id="confirmation-error"><?= htmlspecialchars($errors['password_confirmation'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

      <button type="submit">Create account</button>
    </form>
    <p class="alternate">Already registered? <a href="/login">Log in</a>.</p>
  </main>
</body>
</html>
