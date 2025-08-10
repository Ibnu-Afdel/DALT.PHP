<header class="border-b border-[#93DA97] bg-white/70 backdrop-blur">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2">
      <span class="inline-block w-2 h-6 bg-[#3E5F44] rounded"></span>
      <span class="text-xl font-semibold tracking-tight">DALT.PHP</span>
    </a>
    <?php if (file_exists(base_path('storage/auth_example_installed'))): ?>
      <nav class="flex items-center gap-4 text-sm">
        <?php if (\Core\Session::has('user')): ?>
          <form action="/session" method="POST" class="inline">
            <input type="hidden" name="_method" value="DELETE">
            <?= csrf_field() ?>
            <button type="submit" class="hover:underline">Logout</button>
          </form>
        <?php else: ?>
          <a href="/login" class="hover:underline">Login</a>
          <a href="/register" class="hover:underline">Register</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </div>
</header>
