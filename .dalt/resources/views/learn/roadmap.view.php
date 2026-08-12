<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>

<!-- Roadmap Content Data (outside Vue app) -->
<script type="application/json" id="lesson-content-data">
  <?= json_encode($content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
</script>
<script type="application/json" id="roadmap-graph-data">
  <?= json_encode($nodes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
</script>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-6xl px-5 py-10 sm:px-6 sm:py-16">
    <header class="max-w-3xl">
      <p class="font-mono text-xs font-semibold uppercase tracking-[0.16em] text-[#93DA97]">Course map</p>
      <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl">Competency roadmap</h1>
      <p class="mt-5 text-lg leading-8 text-gray-400">See how each concept connects before you decide where to go next.</p>
    </header>

    <div class="mt-12 min-w-0 space-y-8">
      <section id="the-graph" class="scroll-mt-24 rounded-2xl border border-[#1e293b] bg-[#11161d] p-6 sm:p-8">
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

      <article class="rounded-2xl border border-[#1e293b] bg-[#11161d]">
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
  </div>
</main>

<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
