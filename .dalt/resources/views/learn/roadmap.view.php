<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>
<?php
$totalLessons = count($lessons);
$completedCount = count($completedLessonIds);
$verifiedCount = count($verifiedLessonIds);
$progress = $totalLessons > 0 ? (int) round(($completedCount / $totalLessons) * 100) : 0;
?>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-4xl px-5 py-10 sm:px-6 sm:py-16">
    <a href="/learn" class="inline-flex items-center text-sm font-medium text-gray-500 transition-colors hover:text-[#93DA97]"><span class="mr-2" aria-hidden="true">←</span>Learning dashboard</a>

    <header class="mt-10 max-w-3xl border-b border-[#1e293b] pb-10">
      <h1 class="text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl">Learning roadmap</h1>
      <p class="mt-5 text-lg leading-8 text-gray-400">The full DALT curriculum, organized by learning path. Follow one subject at a time or use the connections as gentle guidance between them.</p>
      <div class="mt-8 grid gap-6 border-y border-[#1e293b] py-6 sm:grid-cols-[1fr_auto] sm:items-end">
        <div>
          <p class="text-2xl font-bold tracking-tight text-gray-100"><?= $completedCount ?> of <?= $totalLessons ?> lessons completed</p>
          <p class="mt-1 text-sm text-gray-500"><?= $verifiedCount ?> verified through practice</p>
        </div>
        <div class="sm:w-40">
          <div class="h-1.5 overflow-hidden rounded-full bg-[#1e293b]" role="progressbar" aria-label="Overall curriculum progress" aria-valuemin="0" aria-valuemax="<?= $totalLessons ?>" aria-valuenow="<?= $completedCount ?>"><div class="h-full rounded-full bg-[#93DA97]" style="width: <?= $progress ?>%"></div></div>
          <p class="mt-2 text-right font-mono text-xs text-gray-500"><?= $progress ?>% complete</p>
        </div>
      </div>
      <?php if ($continuation !== null): ?><p class="mt-6 text-sm text-gray-400">Continue from <a href="/learn/lessons/<?= htmlspecialchars($continuation['id']) ?>" class="font-semibold text-[#93DA97] hover:text-[#b5edb8]"><?= htmlspecialchars($continuation['title']) ?> <span aria-hidden="true">→</span></a></p><?php endif; ?>
    </header>

    <div class="mt-12 space-y-14">
      <?php foreach ($paths as $path): ?>
        <?php $section = $path['section']; $sectionLessons = $path['lessons']; ?>
        <section aria-labelledby="<?= htmlspecialchars($section['id']) ?>-title">
          <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2 border-b border-[#1e293b] pb-5">
            <div>
              <h2 id="<?= htmlspecialchars($section['id']) ?>-title" class="text-2xl font-bold tracking-tight text-gray-100"><?= htmlspecialchars($section['title']) ?></h2>
              <p class="mt-1 text-sm leading-6 text-gray-500"><?= htmlspecialchars($section['description']) ?></p>
            </div>
            <p class="shrink-0 font-mono text-sm text-gray-400"><?= $path['completed_count'] ?> / <?= count($sectionLessons) ?></p>
          </div>

          <ol class="divide-y divide-[#1e293b]">
            <?php foreach ($sectionLessons as $lesson): ?>
              <?php
              $verified = isset($verifiedLessonIds[$lesson['id']]);
              $completed = isset($completedLessonIds[$lesson['id']]);
              $inProgress = !$completed && $lastVisitedLesson === $lesson['id'];
              $state = $verified ? 'Verified' : ($completed ? 'Completed' : ($inProgress ? 'In progress' : 'Not started'));
              $symbol = $verified || $completed ? '✓' : ($inProgress ? '→' : '○');
              ?>
              <li class="py-5">
                <div class="flex items-start gap-3 sm:gap-4">
                  <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center font-mono text-sm <?= $verified || $completed ? 'text-[#93DA97]' : ($inProgress ? 'text-amber-300' : 'text-gray-600') ?>" aria-hidden="true"><?= $symbol ?></span>
                  <div class="min-w-0 flex-1">
                    <a href="/learn/lessons/<?= htmlspecialchars($lesson['id']) ?>" class="font-semibold text-gray-200 transition-colors hover:text-[#93DA97]"><?= htmlspecialchars($lesson['title']) ?></a>
                    <?php if ($lesson['recommended_first'] !== []): ?><p class="mt-1 text-sm leading-6 text-gray-500">Recommended first: <?php foreach ($lesson['recommended_first'] as $index => $prerequisite): ?><?= $index > 0 ? ', ' : '' ?><a href="/learn/lessons/<?= htmlspecialchars($prerequisite['id']) ?>" class="text-[#6ea8e0] hover:text-[#9bc7f4]"><?= htmlspecialchars($prerequisite['title']) ?></a><?php endforeach; ?></p><?php endif; ?>
                  </div>
                  <span class="shrink-0 pt-0.5 font-mono text-xs <?= $verified || $completed ? 'text-[#93DA97]' : ($inProgress ? 'text-amber-300' : 'text-gray-500') ?>"><span aria-hidden="true"><?= $symbol ?> </span><?= $state ?></span>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
          <a href="/learn/tracks/<?= htmlspecialchars($section['id']) ?>" class="mt-5 inline-flex items-center text-sm font-semibold text-[#93DA97] transition-colors hover:text-[#b5edb8]">View <?= htmlspecialchars($section['title']) ?> path <span class="ml-2" aria-hidden="true">→</span></a>
        </section>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
