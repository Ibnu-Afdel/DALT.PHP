<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>

<?php
$lessonsBySection = array_fill_keys(array_keys($sections), []);
foreach ($lessons as $lesson) {
    $lessonsBySection[$lesson['section']][] = $lesson;
}
$completedCount = count($completedLessonIds);
$totalLessons = count($lessons);
$progress = $totalLessons > 0 ? (int) round(($completedCount / $totalLessons) * 100) : 0;
?>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-5xl px-5 py-10 sm:px-6 sm:py-16">
    <header class="max-w-2xl">
      <p class="font-mono text-xs font-semibold uppercase tracking-[0.16em] text-[#93DA97]">DALT.PHP learning</p>
      <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl">Keep building your backend instincts.</h1>
      <p class="mt-5 text-lg leading-8 text-gray-400">Pick up where you left off, or choose a subject to study at your own pace.</p>
    </header>

    <section class="mt-12 overflow-hidden rounded-2xl border border-[#93DA97]/25 bg-[#11161d]" aria-labelledby="continue-title">
      <div class="grid gap-8 p-7 sm:p-9 lg:grid-cols-[1fr_14rem] lg:items-end">
        <div>
          <p class="text-sm font-semibold text-[#93DA97]"><?= $currentChallenge !== null ? 'Active challenge' : ($completedCount === 0 ? 'Start here' : 'Continue learning') ?></p>
          <?php if ($currentChallenge !== null): ?>
            <h2 id="continue-title" class="mt-2 text-2xl font-bold tracking-tight text-gray-50"><?= htmlspecialchars($currentChallenge['title']) ?></h2>
            <p class="mt-3 max-w-xl leading-7 text-gray-400">Your challenge is ready. Continue debugging, then run verification when you are ready.</p>
            <a href="/learn/challenges/<?= htmlspecialchars($currentChallenge['id']) ?>" class="mt-6 inline-flex items-center rounded-lg bg-[#93DA97] px-4 py-2.5 text-sm font-bold text-[#0a0d12] transition-colors hover:bg-[#b5edb8]">Continue challenge <span class="ml-2" aria-hidden="true">→</span></a>
          <?php elseif ($nextLesson !== null): ?>
            <h2 id="continue-title" class="mt-2 text-2xl font-bold tracking-tight text-gray-50"><?= htmlspecialchars($nextLesson['title']) ?></h2>
            <p class="mt-3 max-w-xl leading-7 text-gray-400"><?= htmlspecialchars($nextLesson['description']) ?></p>
            <a href="/learn/lessons/<?= htmlspecialchars($nextLesson['id']) ?>" class="mt-6 inline-flex items-center rounded-lg bg-[#93DA97] px-4 py-2.5 text-sm font-bold text-[#0a0d12] transition-colors hover:bg-[#b5edb8]">Continue learning <span class="ml-2" aria-hidden="true">→</span></a>
          <?php endif; ?>
        </div>
        <div class="border-t border-[#93DA97]/15 pt-6 lg:border-l lg:border-t-0 lg:pl-7 lg:pt-0">
          <p class="font-mono text-xs uppercase tracking-wider text-gray-500">Course progress</p>
          <p class="mt-2 text-3xl font-bold text-gray-100"><?= $completedCount ?><span class="text-lg font-medium text-gray-500"> / <?= $totalLessons ?></span></p>
          <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#0a0d12]" role="progressbar" aria-label="Course progress" aria-valuemin="0" aria-valuemax="<?= $totalLessons ?>" aria-valuenow="<?= $completedCount ?>"><div class="h-full rounded-full bg-[#93DA97]" style="width: <?= $progress ?>%"></div></div>
          <p class="mt-2 text-sm text-gray-500"><?= $progress ?>% complete</p>
        </div>
      </div>
    </section>

    <section class="mt-14" aria-labelledby="path-title">
      <div class="flex flex-wrap items-end justify-between gap-4 border-b border-[#1e293b] pb-5"><div><h2 id="path-title" class="text-2xl font-bold text-gray-100">Learning paths</h2><p class="mt-1 text-sm text-gray-500">Choose a subject to explore in a clear, self-paced sequence.</p></div><a href="/learn/roadmap" class="text-sm font-semibold text-[#93DA97] hover:text-[#b5edb8]">View roadmap <span aria-hidden="true">→</span></a></div>
      <ol class="mt-2 divide-y divide-[#1e293b]">
        <?php foreach ($sections as $sectionId => $section): ?>
          <?php $sectionLessons = $lessonsBySection[$sectionId]; $sectionComplete = count(array_filter($sectionLessons, fn (array $lesson): bool => isset($completedLessonIds[$lesson['id']]))); $finished = $sectionLessons !== [] && $sectionComplete === count($sectionLessons); ?>
          <li><a href="/learn/tracks/<?= $sectionId ?>" class="group flex items-center gap-4 py-5 transition-colors hover:bg-[#11161d] sm:px-4"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border <?= $finished ? 'border-[#93DA97]/40 bg-[#93DA97]/10 text-[#93DA97]' : 'border-gray-700 text-gray-500' ?> font-mono text-xs"><?= $finished ? '✓' : str_pad((string) $section['display_order'], 2, '0', STR_PAD_LEFT) ?></span><span class="min-w-0 flex-1"><span class="block font-semibold text-gray-200 group-hover:text-[#93DA97]"><?= htmlspecialchars($section['title']) ?></span><span class="mt-1 block text-sm text-gray-500"><?= htmlspecialchars($section['description']) ?></span></span><span class="hidden font-mono text-xs text-gray-500 sm:block"><?= $sectionComplete ?> / <?= count($sectionLessons) ?></span><span class="text-gray-600 group-hover:text-[#93DA97]" aria-hidden="true">→</span></a></li>
        <?php endforeach; ?>
      </ol>
    </section>

    <section class="mt-12 flex flex-col justify-between gap-5 rounded-xl border border-[#1e293b] bg-[#11161d] p-6 sm:flex-row sm:items-center"><div><h2 class="font-semibold text-gray-100">Want to browse everything?</h2><p class="mt-1 text-sm text-gray-500">All lessons and debugging challenges live in one resource library.</p></div><a href="/learn/resources" class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-semibold text-gray-200 transition-colors hover:border-[#93DA97]/50 hover:text-[#93DA97]">Open resources <span class="ml-2" aria-hidden="true">→</span></a></section>
  </div>
</main>

<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
