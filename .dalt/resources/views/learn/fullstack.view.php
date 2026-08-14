<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>
<?php $totalLessons = count($availableLessonIds); $completedCount = count(array_filter($availableLessonIds, fn (string $id): bool => isset($completedLessonIds[$id]))); $progress = $totalLessons === 0 ? 0 : (int) round($completedCount / $totalLessons * 100); ?>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-4xl px-5 py-10 sm:px-6 sm:py-16">
    <a href="/learn" class="inline-flex items-center text-sm font-medium text-gray-500 transition-colors hover:text-[#c4a7ff]"><span class="mr-2" aria-hidden="true">←</span>Learning dashboard</a>

    <header class="mt-10 border-b border-[#2a2038] pb-10">
      <p class="font-mono text-xs font-semibold uppercase tracking-[0.16em] text-[#c4a7ff]">A separate learning journey</p>
      <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl"><?= htmlspecialchars($track['title']) ?></h1>
      <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-400"><?= htmlspecialchars($track['description']) ?></p>
      <div class="mt-8 grid gap-6 border-y border-[#2a2038] py-6 sm:grid-cols-[1fr_auto] sm:items-end">
        <div>
          <p class="font-mono text-sm text-gray-400"><?= $completedCount ?> of <?= $totalLessons ?> available lesson<?= $totalLessons === 1 ? '' : 's' ?> complete</p>
          <p class="mt-2 text-sm text-gray-500">Lessons lead to focused practice, then a Build milestone. Future parts are shown as planned—not completed.</p>
        </div>
        <div class="sm:w-44"><div class="h-1.5 overflow-hidden rounded-full bg-[#1e293b]" role="progressbar" aria-label="Fullstack available lesson progress" aria-valuemin="0" aria-valuemax="<?= $totalLessons ?>" aria-valuenow="<?= $completedCount ?>"><div class="h-full rounded-full bg-[#b58cff]" style="width: <?= $progress ?>%"></div></div><p class="mt-2 text-right font-mono text-xs text-gray-500"><?= $progress ?>% of available lessons</p></div>
      </div>
      <?php if ($continuation !== null): ?><a href="/learn/lessons/<?= htmlspecialchars($continuation['id']) ?>" class="mt-8 inline-flex items-center rounded-lg bg-[#b58cff] px-4 py-2.5 text-sm font-bold text-[#120d1a] transition-colors hover:bg-[#d0b8ff]"><?= $completedCount === 0 ? 'Start with' : 'Continue' ?> <?= htmlspecialchars($continuation['title']) ?><span class="ml-2" aria-hidden="true">→</span></a><?php else: ?><p class="mt-8 font-semibold text-[#c4a7ff]">All currently available Fullstack lessons are complete.</p><?php endif; ?>
    </header>

    <section class="mt-12" aria-labelledby="journey-title">
      <div class="flex flex-wrap items-end justify-between gap-3 border-b border-[#1e293b] pb-5"><div><h2 id="journey-title" class="text-2xl font-bold text-gray-100">The journey</h2><p class="mt-1 text-sm text-gray-500">One application, built in stages. Only published lessons can be opened.</p></div><p class="font-mono text-xs text-gray-600">Part 00 → Part 12</p></div>
      <ol class="divide-y divide-[#1e293b]">
        <?php foreach ($track['parts'] as $number => $part): ?>
          <?php $partNumber = str_pad((string) $number, 2, '0', STR_PAD_LEFT); ?>
          <?php $published = $part['lessons'] !== []; $lessonAvailable = $part['lesson_available']; ?>
          <li class="py-7">
            <div class="grid gap-4 sm:grid-cols-[4.5rem_1fr_auto] sm:items-start sm:gap-6">
              <p class="font-mono text-sm <?= $published ? 'text-[#c4a7ff]' : 'text-gray-600' ?>">PART <?= htmlspecialchars($partNumber) ?></p>
              <div>
                <h3 class="text-lg font-bold text-gray-200"><?= htmlspecialchars($part['title']) ?></h3>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500"><?= htmlspecialchars($part['purpose']) ?></p>
                <?php if ($published && $lessonAvailable): ?><ol class="mt-5 space-y-3" aria-label="Lessons for Part <?= htmlspecialchars($partNumber) ?>"><?php foreach ($part['lessons'] as $lessonId): ?><?php $lesson = $lessonsById[$lessonId]; $done = isset($completedLessonIds[$lessonId]); $available = in_array($lessonId, $part['available_lesson_ids'], true); ?><li><?php if ($available): ?><a href="/learn/lessons/<?= htmlspecialchars($lessonId) ?>" class="group flex items-start gap-3 rounded-lg bg-[#171220] px-4 py-3 transition-colors hover:bg-[#21192d]"><span class="mt-0.5 font-mono text-sm <?= $done ? 'text-[#c4a7ff]' : 'text-gray-500' ?>" aria-hidden="true"><?= $done ? '✓' : '01' ?></span><span><span class="block font-semibold text-gray-200 group-hover:text-[#d0b8ff]"><?= htmlspecialchars($lesson['title']) ?></span><span class="mt-1 block text-sm text-gray-500"><?= htmlspecialchars($lesson['description']) ?></span></span></a><?php else: ?><div class="flex items-start gap-3 rounded-lg bg-[#111019] px-4 py-3"><span class="mt-0.5 font-mono text-sm text-gray-600" aria-hidden="true">·</span><span><span class="block font-semibold text-gray-500"><?= htmlspecialchars($lesson['title']) ?></span><span class="mt-1 block text-sm text-gray-600">Available after <?= htmlspecialchars($lessonsById[$lesson['prerequisites'][0]]['title']) ?></span></span></div><?php endif; ?></li><?php endforeach; ?></ol><?php elseif ($published): ?><p class="mt-4 text-sm text-gray-600">Available after the previous Part is complete</p><?php else: ?><p class="mt-4 text-sm text-gray-600">Planned material · not yet available</p><?php endif; ?>
              </div>
              <div class="border-l border-[#2a2038] pl-4 sm:mt-0">
                <p class="font-mono text-xs uppercase tracking-wider text-gray-600">Build</p>
                <?php foreach ($part['milestones'] as $milestone): ?>
                  <?php if ($milestone['completed']): ?>
                    <p class="mt-2 text-sm text-[#c4a7ff]"><span class="font-mono text-xs">✓ <?= htmlspecialchars($milestone['id']) ?></span> <?= htmlspecialchars($milestone['title']) ?></p>
                  <?php elseif ($milestone['available']): ?>
                    <a href="<?= htmlspecialchars($milestone['route']) ?>" class="mt-2 block text-sm font-semibold text-[#c4a7ff] hover:text-[#d9c9ff]"><span class="font-mono text-xs"><?= htmlspecialchars($milestone['id']) ?></span> <?= htmlspecialchars($milestone['title']) ?> <span aria-hidden="true">→</span></a>
                  <?php else: ?>
                    <p class="mt-2 text-sm <?= $published ? 'text-gray-500' : 'text-gray-600' ?>"><span class="font-mono text-xs"><?= htmlspecialchars($milestone['id']) ?></span> <?= htmlspecialchars($milestone['title']) ?></p>
                  <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($part['is_complete']): ?><p class="mt-4 text-xs font-semibold text-[#c4a7ff]">Part <?= htmlspecialchars($partNumber) ?> complete</p><?php endif; ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>
</main>
<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
