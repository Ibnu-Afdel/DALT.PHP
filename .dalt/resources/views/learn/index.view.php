<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/nav.php') ?>

<main class="flex-1 bg-gray-50" id="app">
  <!-- Header -->
  <section class="bg-gradient-to-r from-[#3E5F44] to-[#2d4532] text-white py-12">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex items-center gap-3 mb-4">
        <a href="/" class="text-white/60 hover:text-white transition-colors">Home</a>
        <span class="text-white/40">/</span>
        <span class="text-white">Learn</span>
      </div>
      <h1 class="text-4xl font-bold mb-3">Interactive Learning</h1>
      <p class="text-xl text-gray-300">Master backend concepts through lessons and hands-on challenges</p>
    </div>
  </section>

  <div class="max-w-7xl mx-auto px-6 py-12">
    <!-- Lessons Section -->
    <section class="mb-16">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-3xl font-bold text-gray-900">Lessons</h2>
          <p class="text-gray-600 mt-1">Learn the fundamentals before tackling challenges</p>
        </div>
        <div class="text-sm text-gray-500">
          <?= count($lessons) ?> lessons available
        </div>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($lessons as $index => $lesson): ?>
          <a href="/learn/lessons/<?= $lesson['id'] ?>" class="block bg-white rounded-xl border-2 border-gray-200 p-6 hover:border-[#93DA97] hover:shadow-lg transition-all group">
            <div class="flex items-start justify-between mb-4">
              <div class="text-4xl"><?= $lesson['icon'] ?></div>
              <div class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                Lesson <?= $index + 1 ?>
              </div>
            </div>
            <h3 class="text-xl font-semibold mb-2 group-hover:text-[#3E5F44] transition-colors">
              <?= htmlspecialchars($lesson['title']) ?>
            </h3>
            <p class="text-gray-600 text-sm mb-4">
              <?= htmlspecialchars($lesson['description']) ?>
            </p>
            <div class="flex items-center text-[#3E5F44] text-sm font-medium">
              <span>Read lesson</span>
              <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Challenges Section -->
    <section>
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-3xl font-bold text-gray-900">Challenges</h2>
          <p class="text-gray-600 mt-1">Debug broken code and verify your solutions</p>
        </div>
        <div class="text-sm text-gray-500">
          <?= count($challenges) ?> challenges available
        </div>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($challenges as $challenge): ?>
          <a href="/learn/challenges/<?= $challenge['id'] ?>" class="block bg-white rounded-xl border-2 border-gray-200 p-6 hover:border-[#93DA97] hover:shadow-lg transition-all group">
            <div class="flex items-start justify-between mb-4">
              <div class="text-4xl"><?= $challenge['icon'] ?></div>
              <div class="flex flex-col gap-2 items-end">
                <span class="px-3 py-1 bg-<?= $challenge['color'] ?>-100 text-<?= $challenge['color'] ?>-700 text-xs font-semibold rounded-full">
                  <?= $challenge['difficulty'] ?>
                </span>
              </div>
            </div>
            <h3 class="text-xl font-semibold mb-2 group-hover:text-[#3E5F44] transition-colors">
              <?= htmlspecialchars($challenge['title']) ?>
            </h3>
            <p class="text-gray-600 text-sm mb-4">
              <?= htmlspecialchars($challenge['description']) ?>
            </p>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500"><?= $challenge['bugs'] ?> bug<?= $challenge['bugs'] > 1 ? 's' : '' ?> to fix</span>
              <div class="flex items-center text-[#3E5F44] text-sm font-medium">
                <span>Start</span>
                <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Getting Started -->
    <section class="mt-16 bg-blue-50 border border-blue-200 rounded-xl p-8">
      <div class="flex items-start gap-4">
        <div class="text-3xl">💡</div>
        <div>
          <h3 class="text-xl font-semibold text-blue-900 mb-2">New to DALT?</h3>
          <p class="text-blue-800 mb-4">
            Start with Lesson 1 to understand the request lifecycle, then work through challenges in order. 
            Each challenge has hints and automatic verification to guide you.
          </p>
          <div class="flex gap-3">
            <a href="/learn/lessons/01-request-lifecycle" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
              Start with Lesson 1
            </a>
            <a href="https://github.com/Ibnu-Afdel/DALT.PHP/blob/main/TESTING_GUIDE.md" target="_blank" class="px-4 py-2 bg-white text-blue-600 border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors text-sm font-medium">
              Read Testing Guide
            </a>
          </div>
        </div>
      </div>
    </section>
  </div>
</main>

<?php require base_path('.dalt/resources/views/layouts/footer.php') ?>
