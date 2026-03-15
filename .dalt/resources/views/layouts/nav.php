<header class="border-b border-[#1e293b] bg-[#0f1117]/80 backdrop-blur text-gray-200 sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 group">
      <span class="inline-block w-2 h-6 bg-[#93DA97] rounded group-hover:shadow-[0_0_10px_#93DA97] transition-all"></span>
      <span class="text-xl font-bold tracking-tight text-white group-hover:text-gray-200 transition-colors">DALT.PHP</span>
    </a>
    <nav class="flex items-center gap-6 text-sm">
      <a href="/learn" class="font-medium hover:text-[#93DA97] transition-colors">Learn</a>
      <?php if (file_exists(base_path('storage/auth_example_installed'))): ?>
        <?php if (\Core\Session::has('user')): ?>
          <form action="/session" method="POST" class="inline">
            <input type="hidden" name="_method" value="DELETE">
            <?= csrf_field() ?>
            <button type="submit" class="text-gray-400 hover:text-red-400 transition-colors">Logout</button>
          </form>
        <?php else: ?>
          <a href="/login" class="text-gray-400 hover:text-white transition-colors">Login</a>
          <a href="/register" class="text-gray-400 hover:text-white transition-colors">Register</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
  </div>
</header>
