<?php require base_path('resources/views/layouts/head.php') ?>
<?php require base_path('resources/views/layouts/nav.php') ?>

<main class="min-h-screen">
  <!-- Hero -->
  <section class="hero min-h-[60vh] bg-gradient-to-br from-[#3E5F44] via-[#5E936C] to-[#93DA97]">
    <div class="hero-overlay bg-black/10"></div>
    <div class="hero-content text-center text-[#E8FFD7]">
      <div class="max-w-3xl">
        <h1 class="text-5xl font-extrabold tracking-tight">DALT.PHP</h1>
        <p class="mt-4 text-lg opacity-90">A framework to learn PHP by building — SQLite‑first, Tailwind + DaisyUI + Vite, and Alpine.js.</p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
          <a href="/" class="btn" style="background-color:#3E5F44;border-color:#3E5F44;color:#E8FFD7">Get Started</a>
          <a href="https://github.com/" target="_blank" class="btn btn-outline border-[#E8FFD7] text-[#E8FFD7]">GitHub</a>
          <a href="#intro-video" class="btn btn-outline border-[#E8FFD7] text-[#E8FFD7]">Intro Video</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Quickstart + Tools -->
  <section class="mx-auto max-w-6xl px-6 py-12">
    <div class="grid gap-8 md:grid-cols-2">
      <!-- Quickstart -->
      <div x-data="{ open: true }" class="card bg-base-200 shadow border border-base-300">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <h2 class="card-title">Quickstart</h2>
            <button class="btn btn-sm" @click="open = !open">Toggle</button>
          </div>
          <div x-show="open" x-collapse class="mt-4">
            <div class="mockup-code text-sm">
              <pre data-prefix="$"><code>composer install</code></pre>
              <pre data-prefix="$"><code>npm install</code></pre>
              <pre data-prefix="$"><code>cp .env.example .env</code></pre>
              <pre data-prefix="$"><code>php artisan migrate</code></pre>
              <pre data-prefix="$"><code>npm run dev</code></pre>
              <pre data-prefix="$"><code>php artisan serve</code></pre>
            </div>
            <p class="mt-4 text-sm opacity-70">SQLite is the default. Switch to Postgres in <span class="font-mono">.env</span> when ready.</p>
          </div>
        </div>
      </div>

      <!-- Links -->
      <div id="intro-video" class="card bg-base-200 shadow border border-base-300">
        <div class="card-body">
          <h2 class="card-title">Links</h2>
          <p class="opacity-80">Follow along, watch the intro, and say hi.</p>
          <div class="grid grid-cols-2 gap-3 mt-4">
            <a href="#" target="_blank" class="btn w-full" style="background-color:#5E936C;border-color:#5E936C;color:#E8FFD7">YouTube</a>
            <a href="https://github.com/" target="_blank" class="btn btn-outline">GitHub</a>
            <a href="#" target="_blank" class="btn btn-outline">Twitter (X)</a>
            <a href="#" target="_blank" class="btn btn-outline">Telegram</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Tools -->
    <div class="mt-12">
      <h3 class="text-xl font-semibold mb-4">Tools included</h3>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Card template repeated -->
        <a href="https://www.php.net/" target="_blank" class="card bg-base-100 border border-base-300 hover:shadow-md transition">
          <div class="card-body">
            <h4 class="card-title">PHP</h4>
            <p class="opacity-80">Modern PHP (8+) — learn the language by building.</p>
          </div>
        </a>
        <a href="https://www.sqlite.org/" target="_blank" class="card bg-base-100 border border-base-300 hover:shadow-md transition">
          <div class="card-body">
            <h4 class="card-title">SQLite</h4>
            <p class="opacity-80">Zero‑config default database for fast starts.</p>
          </div>
        </a>
        <a href="https://www.postgresql.org/" target="_blank" class="card bg-base-100 border border-base-300 hover:shadow-md transition">
          <div class="card-body">
            <h4 class="card-title">PostgreSQL</h4>
            <p class="opacity-80">Switch via <span class="font-mono">.env</span> when you’re ready.</p>
          </div>
        </a>
        <a href="https://tailwindcss.com/" target="_blank" class="card bg-base-100 border border-base-300 hover:shadow-md transition">
          <div class="card-body">
            <h4 class="card-title">Tailwind CSS</h4>
            <p class="opacity-80">Utility classes for rapid UI development.</p>
          </div>
        </a>
        <a href="https://daisyui.com/" target="_blank" class="card bg-base-100 border border-base-300 hover:shadow-md transition">
          <div class="card-body">
            <h4 class="card-title">DaisyUI</h4>
            <p class="opacity-80">Accessible components on top of Tailwind.</p>
          </div>
        </a>
        <a href="https://vitejs.dev/" target="_blank" class="card bg-base-100 border border-base-300 hover:shadow-md transition">
          <div class="card-body">
            <h4 class="card-title">Vite</h4>
            <p class="opacity-80">Fast dev server and production bundling.</p>
          </div>
        </a>
        <a href="https://alpinejs.dev/" target="_blank" class="card bg-base-100 border border-base-300 hover:shadow-md transition">
          <div class="card-body">
            <h4 class="card-title">Alpine.js</h4>
            <p class="opacity-80">Just enough interactivity via small directives.</p>
          </div>
        </a>
      </div>
    </div>
  </section>
</main>

<?php require base_path('resources/views/layouts/footer.php') ?> 