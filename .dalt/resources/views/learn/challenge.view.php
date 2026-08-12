<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <article class="mx-auto max-w-3xl px-5 py-10 sm:px-6 sm:py-16">
    <a href="/learn/resources" class="inline-flex items-center text-sm font-medium text-gray-500 transition-colors hover:text-[#93DA97]">
      <span class="mr-2" aria-hidden="true">←</span> All resources
    </a>

    <header class="mt-10 border-b border-[#1e293b] pb-10">
      <div class="flex items-start gap-5">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border <?= $isActive ? 'border-amber-400/30 bg-amber-400/10 text-amber-300' : ($challenge['passed'] ? 'border-[#93DA97]/20 bg-[#93DA97]/10 text-[#93DA97]' : 'border-gray-700 bg-[#11161d] text-[#d9e3ed]') ?>" aria-hidden="true">
          <span class="[&>svg]:h-6 [&>svg]:w-6"><?= $challenge['icon'] ?></span>
        </div>
        <div class="min-w-0">
          <p class="font-mono text-xs text-gray-500">Challenge · <?= $challenge['bugs'] ?> bug<?= $challenge['bugs'] === 1 ? '' : 's' ?> to trace</p>
          <h1 class="mt-3 text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl"><?= htmlspecialchars($challenge['title']) ?></h1>
          <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-400"><?= htmlspecialchars($challenge['description']) ?></p>
          <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
            <span class="font-semibold <?= $isActive ? 'text-amber-300' : ($challenge['passed'] ? 'text-[#93DA97]' : 'text-gray-300') ?>">Status: <?= $isActive ? 'Active challenge' : ($challenge['passed'] ? 'Challenge passed ✓' : 'Ready to debug') ?></span>
            <span class="text-gray-600" aria-hidden="true">•</span>
            <span class="text-gray-500"><?= htmlspecialchars($challenge['difficulty']) ?></span>
          </div>
        </div>
      </div>

      <?php if ($relatedLesson !== null): ?>
        <aside class="mt-8 border-t border-[#1e293b] pt-6" aria-labelledby="related-lesson-title">
          <h2 id="related-lesson-title" class="text-sm font-semibold text-gray-200">Built on this lesson</h2>
          <p class="mt-1 text-sm leading-6 text-gray-500">Review the underlying concept whenever you need a refresher.</p>
          <a class="mt-4 inline-flex rounded-lg border border-gray-700 px-3 py-2 text-sm font-medium text-gray-300 transition-colors hover:border-[#93DA97]/50 hover:text-[#93DA97]" href="/learn/lessons/<?= htmlspecialchars($relatedLesson['id']) ?>">
            <?= htmlspecialchars($relatedLesson['title']) ?> <span class="ml-2" aria-hidden="true">→</span>
          </a>
        </aside>
      <?php endif; ?>
    </header>

    <div class="py-12">
      <div class="prose prose-invert max-w-none prose-headings:scroll-mt-24 prose-headings:text-gray-100 prose-headings:tracking-tight prose-p:text-gray-400 prose-p:leading-8 prose-a:font-medium prose-a:text-[#93DA97] prose-a:no-underline hover:prose-a:text-[#b5edb8] prose-pre:rounded-xl prose-pre:border prose-pre:border-[#1e293b] prose-pre:bg-[#080b0f] prose-code:text-[#d9e3ed]">
        <?= $renderedContent ?>
      </div>
    </div>

    <aside class="border-y border-[#1e293b] py-8" aria-labelledby="terminal-title">
      <h2 id="terminal-title" class="text-xl font-bold tracking-tight text-gray-100">Work from your terminal</h2>
      <p class="mt-2 max-w-xl leading-7 text-gray-500">Run a check after each change. The log keeps the most recent verification details if you need to inspect them again.</p>
      <dl class="mt-5 grid gap-4 sm:grid-cols-2">
        <div>
          <dt class="mb-2 font-mono text-xs text-gray-500">Verify your fix</dt>
          <dd><code class="block rounded-lg border border-[#1e293b] bg-[#080b0f] px-3 py-2 font-mono text-xs text-[#93DA97] select-all">php artisan challenge:verify</code></dd>
        </div>
        <div>
          <dt class="mb-2 font-mono text-xs text-gray-500">View verification logs</dt>
          <dd><code class="block rounded-lg border border-[#1e293b] bg-[#080b0f] px-3 py-2 font-mono text-xs text-[#93DA97] select-all">cat storage/logs/challenges.log</code></dd>
        </div>
      </dl>
    </aside>

    <section class="border-b border-[#1e293b] py-8">
      <challenge-verifier challenge-id="<?= htmlspecialchars($challengeId, ENT_QUOTES, 'UTF-8') ?>" lesson-title="<?= htmlspecialchars($relatedLesson['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" next-lesson-id="<?= htmlspecialchars($nextLesson['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" next-lesson-title="<?= htmlspecialchars($nextLesson['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" track-id="<?= htmlspecialchars($relatedLesson['section'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <noscript>
          <div class="rounded-xl border border-amber-400/40 bg-amber-400/10 p-5 text-amber-100">
            <h2 class="font-semibold">Browser verification needs JavaScript</h2>
            <p class="mt-2 text-sm">Use <code>php artisan challenge:verify</code> in your terminal instead.</p>
          </div>
        </noscript>
      </challenge-verifier>
    </section>

    <?php if ($previousChallenge !== null || $nextChallenge !== null): ?>
      <nav class="mt-10 grid gap-3 sm:grid-cols-2" aria-label="Challenge pager">
        <?php if ($previousChallenge !== null): ?>
          <a href="/learn/challenges/<?= htmlspecialchars($previousChallenge['id']) ?>" class="group rounded-xl border border-[#1e293b] p-4 transition-colors hover:border-gray-600 hover:bg-[#11161d]"><span class="text-xs font-medium text-gray-500">← Previous challenge</span><span class="mt-1 block font-semibold text-gray-200 group-hover:text-[#93DA97]"><?= htmlspecialchars($previousChallenge['title']) ?></span></a>
        <?php else: ?>
          <span aria-hidden="true"></span>
        <?php endif; ?>
        <?php if ($nextChallenge !== null): ?>
          <a href="/learn/challenges/<?= htmlspecialchars($nextChallenge['id']) ?>" class="group rounded-xl border border-[#1e293b] p-4 transition-colors hover:border-gray-600 hover:bg-[#11161d] <?= $previousChallenge === null ? 'sm:col-start-2' : '' ?>"><span class="text-xs font-medium text-gray-500">Next challenge →</span><span class="mt-1 block font-semibold text-gray-200 group-hover:text-[#93DA97]"><?= htmlspecialchars($nextChallenge['title']) ?></span></a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </article>
</main>

<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
