<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>

<?php $resourceTitle = $section === null ? 'Learning resources' : $sectionLabels[$section] . ' resources'; ?>
<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-7xl px-5 py-10 sm:px-6 sm:py-16">
    <header class="max-w-2xl">
      <p class="font-mono text-xs font-semibold uppercase tracking-[0.16em] text-[#93DA97]">Resource library</p>
      <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl"><?= htmlspecialchars($resourceTitle) ?></h1>
      <p class="mt-5 text-lg leading-8 text-gray-400"><?= $section === null ? 'Browse every lesson and hands-on debugging challenge at your own pace.' : 'Lessons and practical debugging challenges for this part of the course.' ?></p>
    </header>

    <nav class="mt-10 flex flex-wrap gap-2" aria-label="Resource sections">
      <a href="/learn/resources" class="rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= $section === null ? 'bg-[#93DA97]/10 text-[#93DA97]' : 'text-gray-400 hover:bg-[#11161d] hover:text-gray-100' ?>">All resources</a>
      <?php foreach ($sectionLabels as $sectionId => $label): ?>
        <a href="/learn/resources?section=<?= $sectionId ?>" class="rounded-lg px-3 py-2 text-sm font-medium transition-colors <?= $section === $sectionId ? 'bg-[#93DA97]/10 text-[#93DA97]' : 'text-gray-400 hover:bg-[#11161d] hover:text-gray-100' ?>"><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <section class="mt-12" aria-labelledby="lessons-title">
      <div class="flex items-end justify-between gap-4 border-b border-[#1e293b] pb-5"><div><h2 id="lessons-title" class="text-2xl font-bold text-gray-100">Lessons</h2><p class="mt-1 text-sm text-gray-500">Foundational theory for backend systems</p></div><span class="font-mono text-xs text-gray-500"><?= count($lessons) ?> available</span></div>
      <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($lessons as $lesson): ?>
          <a href="/learn/lessons/<?= $lesson['id'] ?>" class="block rounded-xl border border-gray-800 bg-[#11161d] p-6 transition-all hover:border-[#93DA97]/50 hover:bg-[#161d25] group"><div class="flex items-start justify-between mb-4"><div class="text-3xl" aria-hidden="true"><?= $lesson['icon'] ?></div><div class="rounded border border-gray-700 bg-gray-800/50 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-gray-300">Lesson <?= $lesson['order'] ?></div></div><h3 class="mb-2 text-lg font-bold text-gray-200 transition-colors group-hover:text-[#93DA97]"><?= htmlspecialchars($lesson['title']) ?></h3><p class="mb-4 text-sm text-gray-500 line-clamp-2"><?= htmlspecialchars($lesson['description']) ?></p><div class="mt-auto flex items-center text-sm font-medium text-[#93DA97]">Read lesson <span class="ml-1 transition-transform group-hover:translate-x-1" aria-hidden="true">→</span></div></a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="mt-16" aria-labelledby="challenges-title">
      <div class="flex items-end justify-between gap-4 border-b border-[#1e293b] pb-5"><div><h2 id="challenges-title" class="text-2xl font-bold text-gray-100">Challenges</h2><p class="mt-1 text-sm text-gray-500">Debug broken code and verify solutions</p></div><span class="font-mono text-xs text-gray-500"><?= count($challenges) ?> available</span></div>
      <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($challenges as $challenge): ?>
          <?php $isActive = $activeChallenge === $challenge['id']; ?>
          <a href="/learn/challenges/<?= $challenge['id'] ?>" class="block rounded-xl border <?= $isActive ? 'border-amber-500/60' : ($challenge['passed'] ? 'border-emerald-500/40' : 'border-gray-800') ?> bg-[#11161d] p-6 transition-all hover:border-[#93DA97]/50 hover:bg-[#161d25] group"><div class="mb-3 flex items-start justify-between"><div class="text-3xl" aria-hidden="true"><?= $challenge['icon'] ?></div><span class="rounded border px-2.5 py-1 text-xs font-semibold uppercase tracking-wider <?= $isActive ? 'border-amber-500/20 bg-amber-500/10 text-amber-400' : ($challenge['passed'] ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400' : 'border-gray-700 bg-gray-800/50 text-gray-300') ?>"><?= $isActive ? 'Active' : ($challenge['passed'] ? 'Completed' : htmlspecialchars($challenge['difficulty'])) ?></span></div><h3 class="mb-2 text-lg font-bold text-gray-200 transition-colors group-hover:text-[#93DA97]"><?= htmlspecialchars($challenge['title']) ?></h3><p class="mb-4 text-sm text-gray-500 line-clamp-2"><?= htmlspecialchars($challenge['description']) ?></p><div class="mt-auto flex items-center justify-between"><span class="rounded border border-gray-800 bg-[#0a0d12] px-2 py-1 text-xs text-gray-400"><?= $challenge['bugs'] ?> bug<?= $challenge['bugs'] > 1 ? 's' : '' ?></span><span class="flex items-center text-sm font-medium text-[#93DA97]"> <?= $isActive ? 'Continue' : ($challenge['passed'] ? 'Review' : 'Open') ?> <span class="ml-1 transition-transform group-hover:translate-x-1" aria-hidden="true">→</span></span></div></a>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
