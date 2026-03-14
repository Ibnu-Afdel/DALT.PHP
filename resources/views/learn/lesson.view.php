<?php require base_path('resources/views/layouts/head.php') ?>
<?php require base_path('resources/views/layouts/nav.php') ?>

<!-- Lesson Content Data (outside Vue app) -->
<script type="application/json" id="lesson-content-data">
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
        <span class="text-white">Lessons</span>
      </div>
      <div class="flex items-center gap-4">
        <div class="text-5xl"><?= $lesson['icon'] ?></div>
        <div>
          <h1 class="text-3xl font-bold"><?= htmlspecialchars($lesson['title']) ?></h1>
          <p class="text-gray-300 mt-1">Master the fundamentals</p>
        </div>
      </div>
    </div>
  </section>

  <div class="max-w-5xl mx-auto px-6 py-12">
    <!-- Navigation -->
    <div class="flex items-center justify-between mb-8">
      <?php if (isset($lesson['prev'])): ?>
        <a href="/learn/lessons/<?= $lesson['prev'] ?>" class="flex items-center gap-2 text-gray-600 hover:text-[#3E5F44] transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          <span class="font-medium">Previous Lesson</span>
        </a>
      <?php else: ?>
        <div></div>
      <?php endif; ?>

      <a href="/learn" class="text-gray-600 hover:text-[#3E5F44] transition-colors font-medium">
        Back to Learn
      </a>

      <?php if (isset($lesson['next'])): ?>
        <a href="/learn/lessons/<?= $lesson['next'] ?>" class="flex items-center gap-2 text-gray-600 hover:text-[#3E5F44] transition-colors">
          <span class="font-medium">Next Lesson</span>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      <?php else: ?>
        <div></div>
      <?php endif; ?>
    </div>

    <!-- Lesson Content -->
    <article class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12">
      <lesson-content></lesson-content>
    </article>

    <!-- Next Steps -->
    <div class="mt-8 bg-[#3E5F44] text-white rounded-xl p-8">
      <h3 class="text-2xl font-bold mb-3">Ready to Practice?</h3>
      <p class="text-gray-300 mb-6">
        Now that you've learned the concepts, try debugging a real challenge to test your knowledge.
      </p>
      <div class="flex gap-4">
        <?php if (isset($lesson['next'])): ?>
          <a href="/learn/lessons/<?= $lesson['next'] ?>" class="px-6 py-3 bg-white text-[#3E5F44] rounded-lg hover:bg-gray-100 transition-colors font-medium">
            Next Lesson
          </a>
        <?php endif; ?>
        <a href="/learn" class="px-6 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg hover:bg-white/20 transition-colors font-medium">
          View All Challenges
        </a>
      </div>
    </div>
  </div>
</main>

<?php require base_path('resources/views/layouts/footer.php') ?>
