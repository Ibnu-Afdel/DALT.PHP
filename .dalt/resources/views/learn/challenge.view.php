<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/nav.php') ?>

<!-- Challenge Content Data (outside Vue app) -->
<script type="application/json" id="challenge-content-data">
  <?= json_encode($content) ?>
</script>

<main class="flex-1 bg-[#0f1117] text-gray-300 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px]" id="app">
  <!-- Header -->
  <section class="border-b border-[#1e293b] bg-[#161b22]/50 py-8">
    <div class="max-w-5xl mx-auto px-6">
      <div class="flex items-center gap-3 mb-4 text-sm font-medium">
        <a href="/" class="text-gray-500 hover:text-gray-300 transition-colors">Home</a>
        <span class="text-gray-700">/</span>
        <a href="/learn" class="text-gray-500 hover:text-gray-300 transition-colors">Learn</a>
        <span class="text-gray-700">/</span>
        <span class="text-gray-300">Challenges</span>
      </div>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="text-5xl drop-shadow-md"><?= $challenge['icon'] ?></div>
          <div>
            <h1 class="text-3xl font-bold text-gray-50 tracking-tight"><?= htmlspecialchars($challenge['title']) ?></h1>
            <div class="flex items-center gap-3 mt-2">
              <span class="px-2.5 py-1 bg-[#93DA97]/10 border border-[#93DA97]/20 text-[#93DA97] rounded text-[10px] uppercase tracking-wider font-bold">
                <?= $challenge['difficulty'] ?>
              </span>
              <span class="text-gray-400 text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
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
      <a href="/learn" class="px-4 py-2 bg-[#161b22] border border-gray-700 text-gray-300 rounded-lg hover:bg-[#1e293b] hover:border-gray-600 transition-colors text-sm font-medium flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Learn
      </a>
      <a href="/learn/lessons/<?= $challenge['lesson'] ?>" class="px-4 py-2 bg-[#161b22] border border-gray-700 text-gray-300 rounded-lg hover:bg-[#1e293b] hover:border-gray-600 transition-colors text-sm font-medium flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg> Related Lesson
      </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Challenge Description -->
        <div class="bg-[#161b22] rounded-xl shadow-lg border border-gray-800 p-8 prose prose-invert prose-pre:bg-[#0d1117] prose-pre:border prose-pre:border-gray-800 max-w-none">
          <challenge-content></challenge-content>
        </div>

        <!-- Verification Section -->
        <div class="bg-[#161b22] rounded-xl shadow-lg border border-gray-800 p-8">
          <challenge-verifier challenge-id="<?= $challengeId ?>"></challenge-verifier>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Progress Card -->
        <div class="bg-[#161b22] rounded-xl shadow-lg border border-gray-800 p-6">
          <h3 class="font-bold text-gray-100 mb-4 tracking-wide uppercase text-xs">Challenge Info</h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-gray-500">Difficulty</span>
              <span class="font-medium text-gray-300"><?= $challenge['difficulty'] ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500">Bugs to Fix</span>
              <span class="font-medium text-red-400"><?= $challenge['bugs'] ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500">Challenge ID</span>
              <span class="font-mono text-[10px] bg-[#0d1117] border border-gray-800 text-[#93DA97] px-2 py-1 rounded"><?= $challengeId ?></span>
            </div>
          </div>
        </div>

        <!-- CLI Commands -->
        <div class="bg-[#0d1117] rounded-xl border border-gray-800 p-6 text-gray-300 shadow-lg">
          <h3 class="font-bold mb-4 flex items-center gap-2 text-xs uppercase tracking-wide text-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Terminal Commands
          </h3>
          <div class="space-y-4 text-xs font-mono">
            <div>
              <div class="text-gray-500 mb-1"># Verify your fix</div>
              <div class="bg-black/60 border border-gray-800 rounded px-3 py-2 text-[#93DA97] select-all break-all">
                php artisan challenge:verify
              </div>
            </div>
            <div>
              <div class="text-gray-500 mb-1"># View logs</div>
              <div class="bg-black/60 border border-gray-800 rounded px-3 py-2 text-[#93DA97] select-all break-all">
                cat storage/logs/challenges.log
              </div>
            </div>
          </div>
        </div>

        <!-- Tips -->
        <div class="bg-[#161b22] border border-blue-500/20 rounded-xl p-6 relative overflow-hidden">
          <div class="absolute top-0 left-0 w-1 h-full bg-blue-500/50"></div>
          <h3 class="font-bold text-gray-100 mb-3 flex items-center gap-2 text-xs uppercase tracking-wide">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            Tips
          </h3>
          <ul class="space-y-2 text-sm text-gray-400">
            <li class="flex items-start gap-2">
              <span class="text-blue-500/50 mt-0.5">•</span>
              <span>Read the challenge description carefully</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-500/50 mt-0.5">•</span>
              <span>Check the related lesson first</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-500/50 mt-0.5">•</span>
              <span>Run verification after each fix</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require base_path('.dalt/resources/views/layouts/footer.php') ?>