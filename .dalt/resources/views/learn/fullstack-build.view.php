<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-4xl px-5 py-10 sm:px-6 sm:py-16">
    <a href="/learn/fullstack" class="inline-flex items-center text-sm font-medium text-gray-400 transition-colors hover:text-[#c4a7ff]"><span class="mr-2" aria-hidden="true">←</span>Back to DALT Fullstack</a>

    <header class="mt-10 border-b border-[#2a2038] pb-8">
      <p class="font-mono text-xs font-semibold uppercase tracking-[0.16em] text-[#c4a7ff]">
        Build <?= htmlspecialchars($milestoneId) ?> · Part <?= htmlspecialchars($partNumber) ?> — <?= htmlspecialchars($partTitle) ?>
      </p>
      <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl"><?= htmlspecialchars($title) ?></h1>
      <?php if ($isCompleted): ?>
        <p class="mt-5 inline-flex items-center rounded-full border border-[#c4a7ff]/30 bg-[#c4a7ff]/10 px-3 py-1 text-sm font-semibold text-[#c4a7ff]"><?= htmlspecialchars($milestoneId) ?> marked complete</p>
      <?php endif; ?>
    </header>

    <article class="learn-prose prose prose-invert mt-10 max-w-none prose-headings:scroll-mt-24 prose-a:font-medium prose-a:text-[#c4a7ff] prose-a:no-underline hover:prose-a:text-[#d9c9ff]">
      <?= $renderedContent ?>
    </article>

    <?php /* One deliberate action, labelled for exactly what it is. The milestone
             teaches and specifies; it does not collect, grade, or store any of the
             learner's work. IMPLEMENTATION_PLAN.md 4.8. */ ?>
    <section class="mt-16 border-t border-[#2a2038] pt-10" aria-labelledby="milestone-completion">
      <h2 id="milestone-completion" class="text-xl font-bold text-gray-100">Marking <?= htmlspecialchars($milestoneId) ?> complete</h2>
      <?php if ($isCompleted): ?>
        <p class="mt-3 max-w-2xl leading-7 text-gray-400">You marked this milestone complete. The specification stays here — come back to it whenever the next part builds on something you set up.</p>
        <a href="/learn/fullstack" class="ui-button ui-button-secondary mt-6 inline-flex">Back to the journey <span class="ml-1" aria-hidden="true">→</span></a>
      <?php else: ?>
        <p class="mt-3 max-w-2xl leading-7 text-gray-400">
          Nothing here is checked automatically, and nothing you typed anywhere is stored. This marks your own judgement that the acceptance criteria above are met by software you actually built and ran.
        </p>
        <p class="mt-3 max-w-2xl leading-7 text-gray-500">
          The only person a premature tick costs anything is you. If a criterion is not met, leave this and go back to it — the milestone will still be here.
        </p>
        <form method="post" action="<?= htmlspecialchars($completeAction) ?>" class="mt-6">
          <?= csrf_field() ?>
          <button type="submit" class="ui-button ui-button-primary">Mark <?= htmlspecialchars($milestoneId) ?> complete <span aria-hidden="true">→</span></button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
