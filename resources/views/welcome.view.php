<?php require base_path('resources/views/layouts/head.php') ?>
<?php require base_path('resources/views/layouts/nav.php') ?>

<main class="flex-1">
  <section class="px-6 py-20 s-container">
    <div class="max-w-4xl mx-auto text-center">
      <img src="/favicon.svg" alt="DALT.PHP logo" class="mx-auto mb-6 w-16 h-16" />
      <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">Learn PHP by Building</h1>
      <p class="mt-4 text-base sm:text-lg">DALT.PHP is a minimal starter focused on clarity. SQLite by default. Use Tailwind, DaisyUI, Vite, and Alpine.js only when you need them.</p>
    </div>
  </section>

  <section id="getting-started" class="px-6 pb-16 s-container">
    <div class="max-w-4xl mx-auto">
      <div class="rounded-xl border bg-white p-6 s-card">
        <h2 class="text-xl font-semibold">Getting Started</h2>
        <div class="mt-4 overflow-x-auto">
          <div class="text-sm font-mono bg-gray-800 text-gray-300 rounded p-3">
            <div>$ composer install</div>
            <div>$ npm install</div>
            <div>$ cp .env.example .env</div>
            <div>$ php artisan migrate</div>
            <div>$ npm run dev</div>
            <div>$ php artisan serve</div>
          </div>
        </div>
        <p class="mt-3 text-sm">Routes: <span class="font-mono">routes/routes.php</span> · Controllers: <span class="font-mono">Http/controllers/</span> · Views: <span class="font-mono">resources/views/</span></p>
        <div class="mt-4 text-sm">
          <div class="rounded-lg border bg-gray-100 p-3">
            <p>Want /login and /register?</p>
            <p class="mt-1 font-mono">php artisan example:install auth</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="px-6 pb-24 s-container">
    <div class="max-w-4xl mx-auto grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div class="rounded-xl border bg-white p-6 s-card">
        <h3 class="font-semibold">Links</h3>
        <ul class="mt-3 space-y-2 text-sm">
          <li><a href="https://github.com/Ibnu-Afdel/DALT.PHP" target="_blank" class="underline">GitHub</a></li>
          <li><a href="https://t.me/daltphp" target="_blank" class="underline">Telegram</a></li>
        </ul>
      </div>
      <div class="rounded-xl border bg-white p-6 s-card">
        <h3 class="font-semibold">Stack</h3>
        <p class="mt-3 text-sm">PHP 8+, SQLite by default (Postgres ready), Vite, Tailwind, DaisyUI, Alpine.js.</p>
      </div>
      <div class="rounded-xl border bg-white p-6 s-card">
        <h3 class="font-semibold">Requirements</h3>
        <p class="mt-3 text-sm">Try PHP in your browser first at <a href="https://php.new" target="_blank" class="underline">php.new</a>. Ready to install locally? Get PHP and Composer for your OS:</p>
        <ul class="mt-3 space-y-2 text-sm list-disc pl-5">
          <li><a href="https://www.php.net/downloads.php" target="_blank" class="underline">PHP downloads</a></li>
          <li><a href="https://getcomposer.org/download/" target="_blank" class="underline">Composer install (Windows/macOS/Linux)</a></li>
          <li class="opacity-70">macOS: you can also use Homebrew (brew install php)</li>
          <li class="opacity-70">Linux: use your package manager (apt/yum/pacman) or the official PHP builds</li>
          <li class="opacity-70">Windows: Composer provides an installer; PHP is available via packages like XAMPP or winget</li>
        </ul>
      </div>
    </div>
  </section>
</main>

<?php require base_path('resources/views/layouts/footer.php') ?> 