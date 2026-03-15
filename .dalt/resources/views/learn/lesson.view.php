<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/nav.php') ?>

<!-- Lesson Content Data (outside Vue app) -->
<script type="application/json" id="lesson-content-data">
  <?= json_encode($content) ?>
</script>

<main class="flex-1 bg-[#0f1117] text-gray-300 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px]" id="app">
  <!-- Header -->
  <section class="border-b border-[#1e293b] bg-[#161b22]/50 py-8">
    <div class="max-w-4xl mx-auto px-6">
      <div class="flex items-center gap-3 mb-4 text-sm font-medium">
        <a href="/" class="text-gray-500 hover:text-gray-300 transition-colors">Home</a>
        <span class="text-gray-700">/</span>
        <a href="/learn" class="text-gray-500 hover:text-gray-300 transition-colors">Learn</a>
        <span class="text-gray-700">/</span>
        <span class="text-gray-300">Lessons</span>
      </div>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="text-5xl drop-shadow-md"><?= $lesson['icon'] ?></div>
          <div>
            <h1 class="text-3xl font-bold text-gray-50 tracking-tight"><?= htmlspecialchars($lesson['title']) ?></h1>
            <p class="text-gray-400 mt-2"><?= htmlspecialchars($lesson['description']) ?></p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="max-w-4xl mx-auto px-6 py-12">
    <!-- Quick Actions -->
    <div class="mb-8 flex flex-wrap gap-4">
      <a href="/learn" class="px-4 py-2 bg-[#161b22] border border-gray-700 text-gray-300 rounded-lg hover:bg-[#1e293b] hover:border-gray-600 transition-colors text-sm font-medium flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Back to Learn
      </a>
      
      <?php 
      // Find the first related challenge if it exists
      $relatedChallengeId = null;
      $challengesConfig = json_decode(file_get_contents(base_path('course/challenges/README.md')), true);
      if ($challengesConfig) {
          foreach ($challengesConfig as $id => $challenge) {
              if (isset($challenge['lesson']) && $challenge['lesson'] === $lessonId) {
                  $relatedChallengeId = $id;
                  break;
              }
          }
      }
      if ($relatedChallengeId): 
      ?>
      <a href="/learn/challenges/<?= $relatedChallengeId ?>" class="px-4 py-2 bg-[#93DA97]/10 border border-[#93DA97]/20 text-[#93DA97] rounded-lg hover:bg-[#93DA97]/20 transition-colors text-sm font-bold flex items-center gap-2">
        Test Your Knowledge →
      </a>
      <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="bg-[#161b22] rounded-xl shadow-lg border border-gray-800 p-8 md:p-12 mb-12">
      <!-- We render parsed Markdown here using Vue -->
      <div class="prose prose-invert prose-pre:bg-[#0d1117] prose-pre:border prose-pre:border-gray-800 max-w-none prose-headings:text-gray-100 prose-a:text-[#93DA97]">
        <lesson-content></lesson-content>
      </div>
    </div>
    
  </div>
</main>
