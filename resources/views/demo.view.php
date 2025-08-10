<?php require base_path('resources/views/partials/head.php') ?>
<?php require base_path('resources/views/partials/nav.php') ?>

<main class="mx-auto max-w-3xl p-6">
  <h1 class="text-2xl font-bold">Demo Route</h1>
  <p class="mt-4">Captured id: <span class="font-mono"><?= htmlspecialchars($id ?? '') ?></span></p>
</main>

<?php require base_path('resources/views/partials/footer.php') ?> 