<?php require base_path('resources/views/layouts/head.php') ?>
<?php require base_path('resources/views/layouts/nav.php') ?>

<!-- Challenge Content Data (outside Vue app) -->
<script type="application/json" id="challenge-content-data">
  <?= json_encode($content) ?>
</script>

<main class="flex-1 bg-gray-50" id="app">
  <!-- Header -->
  <section class="bg-gradient-to-r from-[#3E5F44] to-[#2d4532] text-white py-8">
    <div class="max-w-5xl mx-auto px-6">
      <div class="flex items-center gap-3 mb-4 text-sm">
        <a href="/" class="text-white/60 hover:text-white transition-colors">Home</a>
        <span class="text-white/40">/</span>
        <a href="/learn" class="text-white/60 hover:text-white transition-colors">Learn</a>
        <span class="text-white/40">/</span>
        <span class="text-white">Challenges</span>
      </div>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="text-5xl"><?= $challenge['icon'] ?></div>
          <div>
            <h1 class="text-3xl font-bold"><?= htmlspecialchars($challenge['title']) ?></h1>
            <div class="flex items-center gap-3 mt-2">
              <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">
                <?= $challenge['difficulty'] ?>
              </span>
              <span class="text-gray-300 text-sm">
                <?= $challenge['bugs'] ?> bug<?= $challenge['bugs'] > 1 ? 's' : '' ?> to fix
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="max-w-5xl mx-auto px-6 py-12">
    <!-- Quick Actions -->
    <div class="mb-8 flex flex-wrap gap-4">
      <a href="/learn" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
        ← Back to Learn
      </a>
      <a href="/learn/lessons/<?= $challenge['lesson'] ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
        📖 Related Lesson
      </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Challenge Description -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
          <challenge-content></challenge-content>
        </div>

        <!-- Verification Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
          <challenge-verifier challenge-id="<?= $challengeId ?>"></challenge-verifier>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Progress Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="font-semibold text-lg mb-4">Challenge Info</h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Difficulty</span>
              <span class="font-medium"><?= $challenge['difficulty'] ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Bugs to Fix</span>
              <span class="font-medium"><?= $challenge['bugs'] ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Challenge ID</span>
              <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded"><?= $challengeId ?></span>
            </div>
          </div>
        </div>

        <!-- CLI Commands -->
        <div class="bg-gray-900 rounded-xl p-6 text-white">
          <h3 class="font-semibold mb-4 flex items-center gap-2">
            <span>💻</span>
            <span>CLI Commands</span>
          </h3>
          <div class="space-y-3 text-sm font-mono">
            <div>
              <div class="text-gray-400 text-xs mb-1"># Verify your fix</div>
              <div class="bg-black/30 rounded px-3 py-2 text-[#93DA97]">
                php artisan verify <?= $challengeId ?>
              </div>
            </div>
            <div>
              <div class="text-gray-400 text-xs mb-1"># View logs</div>
              <div class="bg-black/30 rounded px-3 py-2 text-[#93DA97]">
                cat storage/logs/challenges.log
              </div>
            </div>
          </div>
        </div>

        <!-- Tips -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
          <h3 class="font-semibold text-blue-900 mb-3 flex items-center gap-2">
            <span>💡</span>
            <span>Tips</span>
          </h3>
          <ul class="space-y-2 text-sm text-blue-800">
            <li class="flex items-start gap-2">
              <span class="text-blue-600 mt-0.5">•</span>
              <span>Read the challenge description carefully</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-600 mt-0.5">•</span>
              <span>Check the related lesson first</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-600 mt-0.5">•</span>
              <span>Use hints if you get stuck</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-600 mt-0.5">•</span>
              <span>Run verification after each fix</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require base_path('resources/views/layouts/footer.php') ?>
