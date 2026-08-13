<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>
<?php $resourceTitle = $section === null ? 'All resources' : $sections[$section]['title']; ?>
<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-7xl px-5 py-10 sm:px-6 sm:py-16">
    <header class="max-w-2xl"><h1 class="text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl"><?= htmlspecialchars($resourceTitle) ?></h1><p class="mt-5 text-lg leading-8 text-gray-400"><?= $section === null ? 'Browse every lesson and hands-on debugging challenge, plus curated external reading for when you want more.' : htmlspecialchars($sections[$section]['description']) ?></p></header>
    <nav class="mt-10 flex flex-wrap gap-2" aria-label="Resource sections"><a href="/learn/resources" class="rounded-lg px-3 py-2 text-sm font-medium <?= $section === null ? 'bg-[#93DA97]/10 text-[#93DA97]' : 'text-gray-400 hover:bg-[#11161d] hover:text-gray-100' ?>">All resources</a><?php foreach ($sections as $sectionId => $sectionMeta): ?><a href="/learn/resources?section=<?= $sectionId ?>" class="rounded-lg px-3 py-2 text-sm font-medium <?= $section === $sectionId ? 'bg-[#93DA97]/10 text-[#93DA97]' : 'text-gray-400 hover:bg-[#11161d] hover:text-gray-100' ?>"><?= htmlspecialchars($sectionMeta['title']) ?></a><?php endforeach; ?></nav>
    <section class="mt-12" aria-labelledby="lessons-title"><div class="flex items-end justify-between gap-4 border-b border-[#1e293b] pb-5"><div><h2 id="lessons-title" class="text-2xl font-bold text-gray-100">Lessons</h2><p class="mt-1 text-sm text-gray-500">Read in any order, or follow a learning path for a focused sequence.</p></div><span class="font-mono text-xs text-gray-500"><?= count($lessons) ?> lessons</span></div>
      <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3"><?php foreach ($lessons as $lesson): ?><a href="/learn/lessons/<?= $lesson['id'] ?>" class="group block rounded-xl border border-gray-800 bg-[#11161d] p-5 transition-colors hover:border-[#93DA97]/50 hover:bg-[#161d25]"><div class="flex items-start justify-between gap-4"><span class="text-[#93DA97]"><?= \Core\Icon::render($lesson['icon'], 'h-6 w-6') ?></span><span class="font-mono text-xs text-gray-500"><?= htmlspecialchars($sections[$lesson['section']]['title']) ?> · <?= $lesson['section_order'] ?></span></div><h3 class="mt-5 text-lg font-bold text-gray-200 group-hover:text-[#93DA97]"><?= htmlspecialchars($lesson['title']) ?></h3><p class="mt-2 line-clamp-2 text-sm leading-6 text-gray-500"><?= htmlspecialchars($lesson['description']) ?></p><span class="mt-5 block text-sm font-semibold text-[#93DA97]">Read lesson <span aria-hidden="true">→</span></span></a><?php endforeach; ?></div>
    </section>
    <section class="mt-16" aria-labelledby="challenges-title"><div class="flex items-end justify-between gap-4 border-b border-[#1e293b] pb-5"><div><h2 id="challenges-title" class="text-2xl font-bold text-gray-100">Challenges</h2><p class="mt-1 text-sm text-gray-500">Debug broken code and verify solutions.</p></div><span class="font-mono text-xs text-gray-500"><?= count($challenges) ?> challenges</span></div>
      <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3"><?php foreach ($challenges as $challenge): ?><?php $isActive = $activeChallenge === $challenge['id']; $state = $isActive ? 'Active · In progress' : ($challenge['passed'] ? 'Completed' : 'Not started'); $stateClass = $isActive ? 'ui-status-progress' : ($challenge['passed'] ? 'ui-status-complete' : 'ui-status-pending'); ?><a href="/learn/challenges/<?= $challenge['id'] ?>" class="group block rounded-xl border <?= $isActive ? 'border-[color:var(--warning)]/50' : ($challenge['passed'] ? 'border-[#93DA97]/35' : 'border-gray-800') ?> bg-[#11161d] p-5 transition-colors hover:border-[#93DA97]/50 hover:bg-[#161d25]"><div class="flex items-start justify-between gap-4"><span class="text-[#93DA97]"><?= \Core\Icon::render($challenge['icon'], 'h-6 w-6') ?></span><span class="ui-status <?= $stateClass ?>"><?= $isActive ? '→' : ($challenge['passed'] ? '✓' : '○') ?> <?= $state ?></span></div><h3 class="mt-5 text-lg font-bold text-gray-200 group-hover:text-[#93DA97]"><?= htmlspecialchars($challenge['title']) ?></h3><p class="mt-2 line-clamp-2 text-sm leading-6 text-gray-500"><?= htmlspecialchars($challenge['description']) ?></p><div class="mt-5 flex items-center justify-between text-sm"><span class="text-gray-500"><?= $challenge['bugs'] ?> bug<?= $challenge['bugs'] > 1 ? 's' : '' ?></span><span class="font-semibold text-[#93DA97]"><?= $isActive ? 'Continue' : ($challenge['passed'] ? 'Review' : 'Open') ?> <span aria-hidden="true">→</span></span></div></a><?php endforeach; ?></div>
    </section>
    <?php if ($externalCategories !== []): ?>
    <section class="mt-16" aria-labelledby="external-title">
      <div class="border-b border-[#1e293b] pb-5"><h2 id="external-title" class="text-2xl font-bold text-gray-100">Go deeper</h2><p class="mt-1 text-sm text-gray-500">Curated outside reading — free, brief, and mapped to a lesson. Not required; DALT's own lessons and Laravel bridges stand on their own.</p></div>
      <div class="mt-8 space-y-10"><?php foreach ($externalCategories as $category): ?>
        <div>
          <h3 class="text-lg font-bold text-gray-200"><?= htmlspecialchars($category['title']) ?></h3>
          <?php if ($category['blurb'] !== null): ?><p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars($category['blurb']) ?></p><?php endif; ?>
          <ul class="mt-4 space-y-3"><?php foreach ($category['links'] as $link): ?>
            <li class="rounded-xl border border-gray-800 bg-[#11161d] p-5">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer" class="font-semibold text-[#93DA97] hover:text-[#b5edb8]"><?= htmlspecialchars($link['title']) ?> <span aria-hidden="true">↗</span></a>
                <span class="font-mono text-xs text-gray-600" title="Fetched and confirmed live on this date">✓ verified <?= htmlspecialchars($link['verified']) ?></span>
              </div>
              <p class="mt-2 text-sm leading-6 text-gray-500"><?= htmlspecialchars($link['read']) ?></p>
            </li>
          <?php endforeach; ?></ul>
        </div>
      <?php endforeach; ?></div>
    </section>
    <?php endif; ?>
  </div>
</main>
<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
