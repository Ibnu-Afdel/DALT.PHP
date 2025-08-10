<?php require base_path('resources/views/layouts/head.php') ?>
<?php require base_path('resources/views/layouts/nav.php') ?>

<main class="mx-auto max-w-md p-6">
  <h1 class="text-2xl font-bold mb-4">Login</h1>
  <?php $errors = Core\Session::get('errors', []); $success = Core\Session::get('success'); ?>
  <?php if ($success): ?>
  <div class="alert alert-success mb-4">
    <span><?= htmlspecialchars($success) ?></span>
  </div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
  <div class="alert alert-error mb-4">
    <span>Please fix the errors below.</span>
  </div>
  <?php endif; ?>
  <form method="POST" action="/session" class="space-y-4">
    <?= csrf_field() ?>
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
    <button class="btn btn-primary w-full" type="submit">Login</button>
  </form>
</main>

<?php require base_path('resources/views/layouts/footer.php') ?> 