<?php require base_path('resources/views/layouts/head.php') ?>
<?php require base_path('resources/views/layouts/nav.php') ?>

<main class="flex-1">
  <section class="px-6 py-20 s-container">
    <div class="max-w-4xl mx-auto text-center">
      <img src="/favicon.svg" alt="DALT.PHP logo" class="mx-auto mb-6 w-16 h-16" />
      <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-[#3E5F44]">Learn PHP by Building</h1>
      <p class="mt-4 text-base sm:text-lg text-[#3E5F44]/80">DALT.PHP is a minimal starter focused on clarity. SQLite by default. Use Tailwind, DaisyUI, Vite, and Alpine.js only when you need them.</p>
      <?php if (file_exists(base_path('storage/auth_example_installed'))): ?>
      <div class="mt-6 inline-flex gap-3">
        <a href="/register" class="s-btn s-btn-primary">Register</a>
        <a href="/login" class="s-btn">Login</a>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <section id="getting-started" class="px-6 pb-16 s-container">
    <div class="max-w-4xl mx-auto">
      <div class="rounded-xl border border-[#93DA97] bg-white p-6 s-card">
        <h2 class="text-xl font-semibold">Getting Started</h2>
        <div class="mt-4 overflow-x-auto">
          <div class="mockup-code text-sm">
            <pre data-prefix="$"><code>composer install</code></pre>
            <pre data-prefix="$"><code>npm install</code></pre>
            <pre data-prefix="$"><code>cp .env.example .env</code></pre>
            <pre data-prefix="$"><code>php artisan migrate</code></pre>
            <pre data-prefix="$"><code>npm run dev</code></pre>
            <pre data-prefix="$"><code>php artisan serve</code></pre>
          </div>
        </div>
        <p class="mt-3 text-sm text-[#3E5F44]/70">Routes: <span class="font-mono">routes/routes.php</span> · Controllers: <span class="font-mono">Http/controllers/</span> · Views: <span class="font-mono">resources/views/</span></p>
        <div class="mt-4 text-sm">
          <div class="rounded-lg border border-[#93DA97] bg-[#E8FFD7]/60 p-3">
            <p class="text-[#3E5F44]/90">Want /login and /register?</p>
            <p class="mt-1 font-mono">php artisan example:install auth</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="px-6 pb-24 s-container">
    <div class="max-w-4xl mx-auto grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div class="rounded-xl border border-[#93DA97] bg-white p-6 s-card">
        <h3 class="font-semibold">Links</h3>
        <ul class="mt-3 space-y-2 text-sm">
          <!-- <li><a href="#" target="_blank" class="link">Intro Video</a></li> -->
          <li><a href="https://github.com/Ibnu-Afdel/DALT.PHP" target="_blank" class="link">GitHub</a></li>
          <!-- <li><a href="#" target="_blank" class="link">Twitter (X)</a></li> -->
          <li><a href="https://t.me/daltphp" target="_blank" class="link">Telegram</a></li>
        </ul>
      </div>
      <div class="rounded-xl border border-[#93DA97] bg-white p-6 s-card">
        <h3 class="font-semibold">Stack</h3>
        <p class="mt-3 text-sm">PHP 8+, SQLite by default (Postgres ready), Vite, Tailwind, DaisyUI, Alpine.js.</p>
      </div>
      <div class="rounded-xl border border-[#93DA97] bg-white p-6 s-card">
        <h3 class="font-semibold">Requirements</h3>
        <p class="mt-3 text-sm">Try PHP in the browser at php.new. When ready, install PHP 8+ and Composer locally.</p>
        <ul class="mt-3 space-y-2 text-sm">
          <li><a href="https://php.new" target="_blank" class="link">Try PHP online (php.new)</a></li>
          <!-- <li><a href="https://www.php.net/" target="_blank" class="link">PHP</a> · <a href="https://getcomposer.org/" target="_blank" class="link">Composer</a></li> -->
        </ul>
      </div>
    </div>
  </section>
</main>

<?php require base_path('resources/views/layouts/footer.php') ?> 