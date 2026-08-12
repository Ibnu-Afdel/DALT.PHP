<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>

<!-- Lesson content remains progressively enhanced by Vue until Phase 03 moves Markdown rendering server-side. -->
<script type="application/json" id="lesson-content-data">
  <?= json_encode($content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
</script>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <article class="mx-auto max-w-3xl px-5 py-10 sm:px-6 sm:py-16">
    <a href="/learn/resources" class="inline-flex items-center text-sm font-medium text-gray-500 transition-colors hover:text-[#93DA97]">
      <span class="mr-2" aria-hidden="true">←</span> All resources
    </a>

    <header class="mt-10 border-b border-[#1e293b] pb-10">
      <div class="flex items-start gap-5">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-[#93DA97]/20 bg-[#93DA97]/10 text-[#93DA97]" aria-hidden="true">
          <span class="[&>svg]:h-6 [&>svg]:w-6"><?= $lesson['icon'] ?></span>
        </div>
        <div class="min-w-0">
          <p class="font-mono text-xs text-gray-500">Lesson <?= $lesson['order'] ?></p>
          <h1 class="mt-3 text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl"><?= htmlspecialchars($lesson['title']) ?></h1>
          <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-400"><?= htmlspecialchars($lesson['description']) ?></p>
        </div>
      </div>

      <?php if ($prerequisites !== []): ?>
        <aside class="mt-8 border-t border-[#1e293b] pt-6" aria-label="Lesson prerequisites">
          <h2 class="text-sm font-semibold text-gray-200">Before you begin</h2>
          <p class="mt-1 text-sm leading-6 text-gray-500">These lessons give this topic its context.</p>
          <ul class="mt-4 flex flex-wrap gap-2">
            <?php foreach ($prerequisites as $prerequisite): ?>
              <li><a class="inline-flex rounded-lg border border-gray-700 px-3 py-2 text-sm font-medium text-gray-300 transition-colors hover:border-[#93DA97]/50 hover:text-[#93DA97]" href="/learn/lessons/<?= $prerequisite['id'] ?>"><?= htmlspecialchars($prerequisite['title']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </aside>
      <?php endif; ?>
    </header>

    <div class="py-12">
      <div class="prose prose-invert max-w-none prose-headings:scroll-mt-24 prose-headings:text-gray-100 prose-headings:tracking-tight prose-p:text-gray-400 prose-p:leading-8 prose-a:font-medium prose-a:text-[#93DA97] prose-a:no-underline hover:prose-a:text-[#b5edb8] prose-pre:rounded-xl prose-pre:border prose-pre:border-[#1e293b] prose-pre:bg-[#080b0f] prose-code:text-[#d9e3ed]">
        <lesson-content>
          <pre class="markdown-fallback"><?= htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
        </lesson-content>
      </div>
    </div>

    <?php if (!empty($relatedChallengeId)): ?>
      <aside class="border-y border-[#1e293b] py-8" aria-labelledby="practice-title">
        <h2 id="practice-title" class="text-xl font-bold tracking-tight text-gray-100">Ready to put this into practice?</h2>
        <p class="mt-2 max-w-xl leading-7 text-gray-500">Open the linked debugging challenge when you are ready to test what you learned.</p>
        <a href="/learn/challenges/<?= $relatedChallengeId ?>" class="mt-5 inline-flex items-center rounded-lg bg-[#93DA97] px-4 py-2.5 text-sm font-bold text-[#0a0d12] transition-colors hover:bg-[#b5edb8]">Open challenge <span class="ml-2" aria-hidden="true">→</span></a>
      </aside>
    <?php endif; ?>

    <?php if ($previousLesson !== null || $nextLesson !== null): ?>
      <nav class="mt-10 grid gap-3 sm:grid-cols-2" aria-label="Lesson pager">
        <?php if ($previousLesson !== null): ?>
          <a href="/learn/lessons/<?= $previousLesson['id'] ?>" class="group rounded-xl border border-[#1e293b] p-4 transition-colors hover:border-gray-600 hover:bg-[#11161d]"><span class="text-xs font-medium text-gray-500">← Previous lesson</span><span class="mt-1 block font-semibold text-gray-200 group-hover:text-[#93DA97]"><?= htmlspecialchars($previousLesson['title']) ?></span></a>
        <?php else: ?>
          <span aria-hidden="true"></span>
        <?php endif; ?>
        <?php if ($nextLesson !== null): ?>
          <a href="/learn/lessons/<?= $nextLesson['id'] ?>" class="group rounded-xl border border-[#1e293b] p-4 transition-colors hover:border-gray-600 hover:bg-[#11161d] <?= $previousLesson === null ? 'sm:col-start-2' : '' ?>"><span class="text-xs font-medium text-gray-500">Next lesson →</span><span class="mt-1 block font-semibold text-gray-200 group-hover:text-[#93DA97]"><?= htmlspecialchars($nextLesson['title']) ?></span></a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </article>
</main>

<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
