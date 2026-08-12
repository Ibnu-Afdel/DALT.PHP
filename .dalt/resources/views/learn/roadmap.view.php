<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/nav.php') ?>

<!-- Roadmap Content Data (outside Vue app) -->
<script type="application/json" id="lesson-content-data">
  <?= json_encode($content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
</script>
<script type="application/json" id="roadmap-graph-data">
  <?= json_encode($nodes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
</script>

<main class="flex-1 bg-[#0f1117] text-gray-300 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px]" id="app" tabindex="-1">
  <section class="border-b border-[#1e293b] bg-[#161b22]/50 py-10">
    <div class="max-w-7xl mx-auto px-6">
      <nav class="flex items-center gap-3 mb-5 text-sm font-medium" aria-label="Breadcrumb">
        <a href="/" class="text-gray-500 hover:text-gray-300 transition-colors">Home</a>
        <span class="text-gray-700" aria-hidden="true">/</span>
        <a href="/learn" class="text-gray-500 hover:text-gray-300 transition-colors">Learn</a>
        <span class="text-gray-700" aria-hidden="true">/</span>
        <span class="text-gray-300" aria-current="page">Roadmap</span>
      </nav>

      <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
        <div>
          <h1 class="max-w-4xl text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl">Competency Roadmap</h1>
          <p class="mt-4 max-w-3xl text-lg leading-8 text-gray-400">
            A dependency-aware path from one PHP request to framework internals, database depth, and learning-platform contribution.
          </p>
        </div>

        <div class="rounded-xl border border-[#93DA97]/20 bg-[#93DA97]/10 p-5">
          <p class="text-xs font-semibold uppercase tracking-wider text-[#93DA97]">How progress works</p>
          <p class="mt-2 text-sm leading-6 text-gray-300">
            Move forward when you can predict, trace, build, test, and explain the behavior—not just when a challenge turns green.
          </p>
        </div>
      </div>
    </div>
  </section>

  <div class="mx-auto grid max-w-7xl gap-8 px-6 py-10 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-start">
    <aside class="lg:sticky lg:top-24" aria-label="Roadmap navigation">
      <nav class="rounded-xl border border-gray-800 bg-[#161b22] p-5">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-200">On this roadmap</h2>
        <ul class="mt-4 space-y-2 text-sm">
          <li><a class="block rounded-lg px-3 py-2 text-[#93DA97] hover:bg-[#93DA97]/10" href="#the-graph">Interactive graph</a></li>
          <li><a class="block rounded-lg px-3 py-2 text-gray-400 hover:bg-[#1e293b] hover:text-gray-200" href="#recommended-foundation-path">Foundation</a></li>
          <li><a class="block rounded-lg px-3 py-2 text-gray-400 hover:bg-[#1e293b] hover:text-gray-200" href="#optional-infrastructure-branches">Infrastructure branches</a></li>
          <li><a class="block rounded-lg px-3 py-2 text-gray-400 hover:bg-[#1e293b] hover:text-gray-200" href="#learning-platform-branch">Platform branch</a></li>
          <li><a class="block rounded-lg px-3 py-2 text-gray-400 hover:bg-[#1e293b] hover:text-gray-200" href="#project-ladder">Project ladder</a></li>
          <li><a class="block rounded-lg px-3 py-2 text-gray-400 hover:bg-[#1e293b] hover:text-gray-200" href="#completion-rubric">Completion rubric</a></li>
        </ul>
      </nav>

      <a href="/learn" class="mt-4 flex items-center justify-between rounded-xl border border-gray-800 bg-[#0d1117] px-4 py-3 text-sm font-medium text-gray-300 transition-colors hover:border-[#93DA97]/40 hover:text-[#93DA97]">
        <span>Back to course</span>
        <span aria-hidden="true">→</span>
      </a>
    </aside>

    <div class="min-w-0 space-y-8">
      <section id="the-graph" class="scroll-mt-24 rounded-xl border border-gray-800 bg-[#161b22] p-6 shadow-lg sm:p-8">
        <h2 class="text-xl font-bold text-gray-100">The graph</h2>
        <p class="mt-1 text-sm text-gray-500">
          Every lesson, with its real prerequisites and practice challenge. Click a node to open the lesson itself; a
          node unlocks once every prerequisite is verified or self-marked.
        </p>
        <div class="mt-6">
          <roadmap-graph>
            <p class="text-sm text-gray-500">Enable JavaScript to browse the roadmap as an interactive graph. The full text version is below.</p>
          </roadmap-graph>
        </div>
      </section>

      <article class="rounded-xl border border-gray-800 bg-[#161b22] shadow-lg">
        <details class="group">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-6 sm:p-8">
            <div>
              <h2 class="text-xl font-bold text-gray-100">Full roadmap reference</h2>
              <p class="mt-1 text-sm text-gray-500">Every node from R00 through the learning-platform and project-ladder branches, in one document.</p>
            </div>
            <svg class="h-5 w-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </summary>
          <div class="border-t border-gray-800 p-6 sm:p-8">
            <div class="prose prose-invert max-w-none prose-headings:scroll-mt-24 prose-headings:text-gray-100 prose-a:text-[#93DA97] prose-pre:border prose-pre:border-gray-800 prose-pre:bg-[#0d1117] prose-code:text-[#93DA97]">
              <lesson-content>
                <pre class="markdown-fallback"><?= htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
              </lesson-content>
            </div>
          </div>
        </details>
      </article>
    </div>
  </div>
</main>

<?php require base_path('.dalt/resources/views/layouts/footer.php') ?>
