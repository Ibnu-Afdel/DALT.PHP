<?php require base_path('resources/views/layouts/head.php') ?>
<?php require base_path('resources/views/layouts/nav.php') ?>

<main class="mx-auto max-w-md p-6">
  <h1 class="text-2xl font-bold mb-4">Register</h1>
  <?php $errors = Core\Session::get('errors', []); ?>
  <?php if (!empty($errors)): ?>
  <div class="alert alert-error mb-4">
    <span>Please fix the errors below.</span>
  </div>
  <?php endif; ?>
  <form method="POST" action="/register" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-sm mb-1">Name</label>
      <input name="name" type="text" value="<?= htmlspecialchars(old('name')) ?>" class="input input-bordered w-full" required />
      <?php if(isset($errors['name'])): ?><p class="text-error text-sm mt-1"><?= $errors['name'] ?></p><?php endif; ?>
    </div>
    <div>
      <label class="block text-sm mb-1">Email</label>
      <input name="email" type="email" value="<?= htmlspecialchars(old('email')) ?>" class="input input-bordered w-full" required />
      <?php if(isset($errors['email'])): ?><p class="text-error text-sm mt-1"><?= $errors['email'] ?></p><?php endif; ?>
    </div>
    <div>
      <label class="block text-sm mb-1">Password</label>
      <input name="password" type="password" class="input input-bordered w-full" required />
      <?php if(isset($errors['password'])): ?><p class="text-error text-sm mt-1"><?= $errors['password'] ?></p><?php endif; ?>
    </div>
    <div>
      <label class="block text-sm mb-1">Confirm Password</label>
      <input name="password_confirmation" type="password" class="input input-bordered w-full" required />
      <?php if(isset($errors['password_confirmation'])): ?><p class="text-error text-sm mt-1"><?= $errors['password_confirmation'] ?></p><?php endif; ?>
    </div>
    <button class="btn btn-primary w-full" type="submit">Create account</button>
  </form>
</main>

<?php require base_path('resources/views/layouts/footer.php') ?> 