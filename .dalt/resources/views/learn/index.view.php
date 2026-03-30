<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/nav.php') ?>

<main class="flex-1 bg-[#0f1117] text-gray-300 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px]" id="app">
  <!-- Header -->
  <section class="border-b border-[#1e293b] bg-[#161b22]/50 py-12">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex items-center gap-3 mb-4 text-sm font-medium">
        <a href="/" class="text-gray-500 hover:text-gray-300 transition-colors">Home</a>
        <span class="text-gray-700">/</span>
        <span class="text-gray-300">Learn</span>
      </div>
      <h1 class="text-4xl font-bold text-gray-50 mb-3 tracking-tight">Interactive Learning</h1>
      <p class="text-lg text-gray-400">Master backend architecture through lessons and hands-on debugging challenges.</p>
    </div>
  </section>

  <div class="max-w-7xl mx-auto px-6 py-12">
    <!-- Lessons Section -->
    <section class="mb-16">
      <div class="flex items-center justify-between mb-6 border-b border-[#1e293b] pb-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-100 flex items-center gap-2">
            <svg class="w-5 h-5 text-[#93DA97]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            Lessons
          </h2>
          <p class="text-gray-500 text-sm mt-1">Foundational theory for backend systems</p>
        </div>
        <div class="text-xs font-mono bg-[#161b22] border border-gray-800 px-2 py-1 rounded text-gray-400">
          <?= count($lessons) ?> available
        </div>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($lessons as $index => $lesson): ?>
          <a href="/learn/lessons/<?= $lesson['id'] ?>" class="block bg-[#161b22] rounded-xl border border-gray-800 p-6 hover:border-[#93DA97]/50 hover:bg-[#1a202c] transition-all group">
            <div class="flex items-start justify-between mb-4">
              <div class="text-3xl"><?= $lesson['icon'] ?></div>
              <div class="px-2.5 py-1 bg-gray-800/50 border border-gray-700 text-gray-300 text-[10px] uppercase tracking-wider font-semibold rounded">
                Lesson <?= $index + 1 ?>
              </div>
            </div>
            <h3 class="text-lg font-bold mb-2 text-gray-200 group-hover:text-[#93DA97] transition-colors">
              <?= htmlspecialchars($lesson['title']) ?>
            </h3>
            <p class="text-gray-500 text-sm mb-4 line-clamp-2">
              <?= htmlspecialchars($lesson['description']) ?>
            </p>
            <div class="flex items-center text-[#93DA97] text-sm font-medium mt-auto">
              <span>Read lesson</span>
              <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
              </svg>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Challenges Section -->
    <section>
      <div class="flex items-center justify-between mb-6 border-b border-[#1e293b] pb-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-100 flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
            </svg>
            Challenges
          </h2>
          <p class="text-gray-500 text-sm mt-1">Debug broken code and verify solutions</p>
        </div>
        <div class="text-xs font-mono bg-[#161b22] border border-gray-800 px-2 py-1 rounded text-gray-400">
          <?= count($challenges) ?> available
        </div>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($challenges as $challenge): ?>
          <a href="/learn/challenges/<?= $challenge['id'] ?>" class="block bg-[#161b22] rounded-xl border border-gray-800 p-6 hover:border-[#93DA97]/50 hover:bg-[#1a202c] transition-all group">
            <div class="flex items-start justify-between mb-3">
              <div class="text-3xl"><?= $challenge['icon'] ?></div>
              <?php
              // Map logical colors to dark-mode-friendly tailwind classes to avoid purge issues
              $colorMap = [
                'red' => 'bg-red-500/10 text-red-400 border-red-500/20',
                'blue' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                'purple' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                'green' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                'yellow' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
              ];
              $mappedClass = $colorMap[$challenge['color']] ?? 'bg-gray-800 text-gray-300 border-gray-700';
              ?>
              <span class="px-2.5 py-1 border text-[10px] uppercase tracking-wider font-semibold rounded <?= $mappedClass ?>">
                <?= $challenge['difficulty'] ?>
              </span>
            </div>
            <h3 class="text-lg font-bold mb-2 text-gray-200 group-hover:text-[#93DA97] transition-colors">
              <?= htmlspecialchars($challenge['title']) ?>
            </h3>
            <p class="text-gray-500 text-sm mb-4 line-clamp-2">
              <?= htmlspecialchars($challenge['description']) ?>
            </p>
            <div class="flex items-center justify-between mt-auto">
              <span class="text-xs bg-[#0d1117] border border-gray-800 px-2 py-1 rounded text-gray-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <?= $challenge['bugs'] ?> bug<?= $challenge['bugs'] > 1 ? 's' : '' ?>
              </span>
              <div class="flex items-center text-[#93DA97] text-sm font-medium">
                <span>Start</span>
                <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Getting Started -->
    <section class="mt-16 bg-[#161b22] border border-blue-500/20 rounded-xl p-8 relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
      <div class="flex items-start gap-4">
        <div class="text-3xl text-yellow-400">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
          </svg>
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-100 mb-2">New to DALT?</h3>
          <p class="text-gray-400 mb-5 max-w-3xl">
            Start with Lesson 1 to understand the request lifecycle, then work through challenges in order.
            Each challenge has hints and automatic verification to guide you directly in your terminal and browser.
          </p>
          <div class="flex flex-wrap gap-3">
            <a href="/learn/lessons/01-request-lifecycle" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-bold shadow-sm">
              Start with Lesson 1
            </a>
            <a href="https://github.com/Ibnu-Afdel/DALT.PHP/blob/main/TESTING_GUIDE.md" target="_blank" class="px-4 py-2 bg-[#0d1117] text-gray-300 border border-gray-700 rounded-lg hover:bg-[#1e293b] hover:text-white transition-colors text-sm font-medium">
              Read Testing Guide
            </a>
          </div>
        </div>
      </div>
    </section>
  </div>
</main>

<?php require base_path('.dalt/resources/views/layouts/footer.php') ?>