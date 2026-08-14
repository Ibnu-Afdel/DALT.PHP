<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>
<?php
$accentClasses = $isFullstack
  ? ['hover:text-[#c4a7ff]', 'border-[#c4a7ff]/20', 'bg-[#c4a7ff]/10', 'text-[#c4a7ff]', 'hover:border-[#c4a7ff]/50', 'hover:text-[#d9c9ff]', 'prose-a:text-[#c4a7ff]', 'hover:prose-a:text-[#d9c9ff]']
  : ['hover:text-[#93DA97]', 'border-[#93DA97]/20', 'bg-[#93DA97]/10', 'text-[#93DA97]', 'hover:border-[#93DA97]/50', 'hover:text-[#b5edb8]', 'prose-a:text-[#93DA97]', 'hover:prose-a:text-[#b5edb8]'];
?>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <article class="mx-auto max-w-3xl px-5 py-10 sm:px-6 sm:py-16">
    <a href="<?= htmlspecialchars($backHref) ?>" class="inline-flex items-center text-sm font-medium text-gray-500 transition-colors <?= $accentClasses[0] ?>">
      <span class="mr-2" aria-hidden="true">←</span> <?= htmlspecialchars($backLabel) ?>
    </a>

    <header class="mt-10 border-b border-[#1e293b] pb-10">
      <div class="flex items-start gap-5">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border <?= $accentClasses[1] ?> <?= $accentClasses[2] ?> <?= $accentClasses[3] ?>" aria-hidden="true">
          <?= \Core\Icon::render($lesson['icon']) ?>
        </div>
        <div class="min-w-0">
          <p class="font-mono text-xs text-gray-500"><?= $isFullstack ? 'DALT Fullstack' : htmlspecialchars($sections[$lesson['section']]['title']) . ' · ' . $lesson['section_order'] . ' of ' . $sectionLessonCount ?></p>
          <h1 class="mt-3 text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl"><?= htmlspecialchars($lesson['title']) ?></h1>
          <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-400"><?= htmlspecialchars($lesson['description']) ?></p>
          <?php if ($isFullstack && $lessonHeaderMeta !== []): ?><p class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500"><?php if (isset($lessonHeaderMeta['effort'])): ?><span><?= htmlspecialchars($lessonHeaderMeta['effort']) ?></span><?php endif; ?><?php if (isset($lessonHeaderMeta['difficulty'])): ?><span><?= htmlspecialchars($lessonHeaderMeta['difficulty']) ?></span><?php endif; ?></p><?php endif; ?>
        </div>
      </div>

      <?php if ($prerequisites !== []): ?>
        <aside class="mt-8 border-t border-[#1e293b] pt-6" aria-label="Lesson prerequisites">
          <h2 class="text-sm font-semibold text-gray-200">Before you begin</h2>
          <p class="mt-1 text-sm leading-6 text-gray-500">These lessons give this topic its context.</p>
          <ul class="mt-4 flex flex-wrap gap-2">
            <?php foreach ($prerequisites as $prerequisite): ?>
              <li><a class="inline-flex rounded-lg border border-gray-700 px-3 py-2 text-sm font-medium text-gray-300 transition-colors <?= $accentClasses[4] ?> <?= $accentClasses[0] ?>" href="/learn/lessons/<?= $prerequisite['id'] ?>"><?= htmlspecialchars($prerequisite['title']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </aside>
      <?php endif; ?>
    </header>

    <div class="py-12">
      <div class="learn-prose prose prose-invert prose-headings:scroll-mt-24 prose-a:font-medium <?= $accentClasses[6] ?> prose-a:no-underline <?= $accentClasses[7] ?>">
        <?= $renderedContent ?>
      </div>

      <?php if ($goDeeperLinks !== []): ?>
        <aside class="mt-10 rounded-xl border border-[#1e293b] bg-[#0d1117] p-6" aria-labelledby="go-deeper-title">
          <h2 id="go-deeper-title" class="text-sm font-semibold text-gray-200">Go deeper</h2>
          <p class="mt-1 text-sm text-gray-500">Optional outside reading for this lesson — free, brief, verified live.</p>
          <ul class="mt-4 space-y-3">
            <?php foreach ($goDeeperLinks as $link): ?>
              <li>
                <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold <?= $accentClasses[3] ?> <?= $accentClasses[5] ?>"><?= htmlspecialchars($link['title']) ?> <span aria-hidden="true">↗</span></a>
                <p class="mt-1 text-sm leading-6 text-gray-500"><?= htmlspecialchars($link['read']) ?></p>
              </li>
            <?php endforeach; ?>
          </ul>
          <a href="/learn/resources?section=<?= htmlspecialchars($lesson['section']) ?>" class="mt-5 inline-block text-xs font-medium text-gray-500 hover:text-[#93DA97]">More resources for this section <span aria-hidden="true">→</span></a>
        </aside>
      <?php endif; ?>
    </div>

    <aside class="border-y border-[#1e293b] py-8" aria-labelledby="practice-title">
      <?php if ($relatedChallenges !== []): ?>
        <h2 id="practice-title" class="text-xl font-bold tracking-tight text-gray-100">Ready to put this into practice?</h2>
        <p class="mt-2 max-w-xl leading-7 text-gray-500">Use a debugging challenge to test what you learned. Practice is optional; completing this lesson is separate from verification.</p>
        <ul class="mt-5 space-y-3">
          <?php foreach ($relatedChallenges as $challenge): ?>
            <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#1e293b] px-4 py-3"><span class="font-medium text-gray-200"><?= $challenge['passed'] ? '✓ ' : '' ?><?= htmlspecialchars($challenge['title']) ?></span><a href="/learn/challenges/<?= htmlspecialchars($challenge['id']) ?>" class="ui-button <?= $isCompleted ? 'ui-button-secondary' : 'ui-button-primary' ?>"><?= $challenge['passed'] ? 'Review challenge' : 'Start challenge' ?> <span aria-hidden="true">→</span></a></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <h2 id="practice-title" class="text-xl font-bold tracking-tight text-gray-100">You've reached the end of <?= htmlspecialchars($lesson['title']) ?>.</h2>
        <p class="mt-2 max-w-xl leading-7 text-gray-500">Mark it complete when you're ready to continue along this learning path.</p>
      <?php endif; ?>

      <?php if ($isVerified): ?>
        <p class="mt-6 font-semibold text-[#93DA97]">Lesson verified ✓</p><p class="mt-1 text-sm text-gray-500">A practical challenge has been passed.</p>
      <?php elseif ($isCompleted): ?>
        <p class="mt-6 font-semibold text-[#93DA97]">Lesson completed ✓</p><?php if ($relatedChallenges !== []): ?><p class="mt-1 text-sm text-gray-500">Practice a challenge when you want to verify your understanding.</p><?php endif; ?>
      <?php elseif ($relatedChallenges !== []): ?>
        <div class="mt-6"><h3 class="font-semibold text-gray-200">Finished the lesson?</h3>
          <form method="post" action="/learn/lessons/<?= htmlspecialchars($lessonId) ?>/complete" class="mt-4"><?= csrf_field() ?><button type="submit" class="ui-button ui-button-secondary">Mark lesson complete</button></form>
        </div>
      <?php endif; ?>
      <?php if (!$isCompleted && $relatedChallenges === []): ?>
        <form method="post" action="/learn/lessons/<?= htmlspecialchars($lessonId) ?>/complete" class="mt-4"><?= csrf_field() ?><input type="hidden" name="continue" value="1"><button type="submit" class="ui-button ui-button-primary">Complete &amp; continue <span aria-hidden="true">→</span></button></form>
      <?php endif; ?>
    </aside>

    <?php if ($previousLesson !== null || $nextLesson !== null): ?>
      <nav class="mt-10 grid gap-3 sm:grid-cols-2" aria-label="Lesson pager">
        <?php if ($previousLesson !== null): ?>
          <a href="/learn/lessons/<?= $previousLesson['id'] ?>" class="group rounded-xl border border-[#1e293b] p-4 transition-colors hover:border-gray-600 hover:bg-[#11161d]"><span class="text-xs font-medium text-gray-500">← Previous in <?= htmlspecialchars($sections[$lesson['section']]['title']) ?></span><span class="mt-1 block font-semibold text-gray-200 group-hover:text-[#93DA97]"><?= htmlspecialchars($previousLesson['title']) ?></span></a>
        <?php else: ?>
          <span aria-hidden="true"></span>
        <?php endif; ?>
        <?php if ($nextLesson !== null): ?>
          <a href="/learn/lessons/<?= $nextLesson['id'] ?>" class="group rounded-xl border border-[#1e293b] p-4 transition-colors hover:border-gray-600 hover:bg-[#11161d] <?= $previousLesson === null ? 'sm:col-start-2' : '' ?>"><span class="text-xs font-medium text-gray-500">Next in <?= htmlspecialchars($sections[$lesson['section']]['title']) ?> →</span><span class="mt-1 block font-semibold text-gray-200 group-hover:text-[#93DA97]"><?= htmlspecialchars($nextLesson['title']) ?></span></a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </article>
</main>

<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
