<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-4xl px-5 py-10 sm:px-6 sm:py-16">
    <a href="/learn/fullstack" class="inline-flex items-center text-sm font-medium text-gray-400 transition-colors hover:text-[#c4a7ff]"><span class="mr-2" aria-hidden="true">←</span>Back to DALT Fullstack</a>

    <header class="mt-10 max-w-3xl border-b border-[#2a2038] pb-10">
      <h1 class="text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl">JavaScript readiness</h1>
      <p class="mt-5 text-lg leading-8 text-gray-400">Evolve one small issue-triage program. You already learned the pieces; this Build is where you retrieve, combine, debug, and explain them.</p>
      <p class="mt-4 text-sm leading-6 text-[#d9c9ff]">Attempt each change yourself first. Use a hint or reveal an explanation only when blocked—the point is to find out whether JavaScript will be friction when TypeScript begins.</p>
    </header>

    <?php if ($isCompleted): ?>
      <div class="mt-10 border-y border-[#2a2038] py-6"><p class="font-semibold text-[#c4a7ff]">B01 completed</p><p class="mt-1 text-sm text-gray-400">Part 01 is complete. Continue to Part 02 when you are ready to begin TypeScript.</p></div>
    <?php else: ?>
      <form method="post" action="/learn/fullstack/build/b01/complete" class="mt-10 space-y-14" data-b01-form>
        <?= csrf_field() ?>
        <section aria-labelledby="workspace-title">
          <h2 id="workspace-title" class="text-2xl font-bold text-gray-100">Set up one small workspace</h2>
          <p class="mt-2 max-w-2xl leading-7 text-gray-400">Keep this work in the repository, but outside the framework and future app. Copy the provided data-only starter, then work in the copy throughout this milestone.</p>
          <pre class="mt-5 overflow-x-auto rounded-lg bg-[#111019] p-4 text-sm leading-6 text-[#d9c9ff]"><code>mkdir -p .dalt/workspace
cp -R .dalt/course/build/B01-javascript-readiness/starter .dalt/workspace/b01-issue-triage
cd .dalt/workspace/b01-issue-triage</code></pre>
          <p class="mt-3 text-sm leading-6 text-gray-500">Create <code>main.mjs</code> first. You will later add <code>issue-tools.mjs</code>. Run with <code>node main.mjs</code>; delete only this workspace directory to reset it.</p>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="read-title">
          <h2 id="read-title" class="text-2xl font-bold text-gray-100">Read, predict, then make Version 1 work</h2>
          <p class="mt-2 max-w-2xl leading-7 text-gray-400">Import the starter’s <code>issues</code> into <code>main.mjs</code> and inspect the data. Before coding, answer these briefly from memory, then build the local transformations below.</p>
          <div class="mt-6 grid gap-5 md:grid-cols-2">
            <?php foreach (['Which operation would return only open issues?', 'Which operation would locate one issue by ID?', 'Which values should be derived instead of manually maintained?', 'If an issue is spread, what is copied and what remains shared?'] as $index => $prompt): ?><label class="block text-sm font-semibold text-gray-200"><?= htmlspecialchars($prompt) ?><textarea required data-b01-draft="predict-<?= $index ?>" class="mt-3 min-h-24 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-3 text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none"></textarea></label><?php endforeach; ?>
          </div>
          <div class="mt-7 max-w-2xl text-sm leading-7 text-gray-400"><p>In your single <code>main.mjs</code>, implement and log functions that:</p><ul class="mt-3 list-disc space-y-1 pl-5"><li>return issues matching a requested status;</li><li>return one issue by ID;</li><li>report whether any issue is high priority and whether every closed issue is assigned;</li><li>derive counts grouped by status and one display label per issue;</li><li>create a status-matching function, then pass that function into your selection; use destructuring where it makes the display label clearer;</li><li>add a tiny immutable helper that accepts remaining labels and returns an issue with those labels.</li></ul><p class="mt-4">Choose the operations that state the result you need. Do not add a UI or a generic data layer.</p></div>
          <label class="mt-6 flex items-start gap-3 text-sm leading-6 text-gray-300"><input required type="checkbox" data-b01-step="v1" class="mt-1 h-4 w-4 accent-[#b58cff]"><span>I ran Version 1 and inspected the input plus each returned result in the terminal.</span></label>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="update-title">
          <h2 id="update-title" class="text-2xl font-bold text-gray-100">Change request: close an issue without changing the input</h2>
          <p class="mt-2 max-w-2xl leading-7 text-gray-400">Add a function that returns a new collection with one issue’s status changed. Log the original collection, the returned collection, and identity comparisons that prove what did and did not change.</p>
          <p class="mt-5 max-w-2xl text-sm leading-7 text-gray-400">Then deliberately reproduce a shallow-copy surprise: make a spread-based copy of issue 17, change its <code>metadata.source</code>, and inspect the original. Repair only the nested branch that needs replacement. Do not use a deep-cloning library.</p>
          <label class="mt-6 block text-sm font-semibold text-gray-200">Why did the original nested value change, and what did your repair copy?<textarea required data-b01-draft="reference-explanation" class="mt-3 min-h-28 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-3 text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none"></textarea></label>
          <label class="mt-4 flex items-start gap-3 text-sm leading-6 text-gray-300"><input required type="checkbox" data-b01-step="immutability" class="mt-1 h-4 w-4 accent-[#b58cff]"><span>I reproduced the shared nested reference, fixed the required level, and confirmed the original collection was preserved for the status update.</span></label>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="modules-title">
          <h2 id="modules-title" class="text-2xl font-bold text-gray-100">Version 3: create one useful module boundary</h2>
          <p class="mt-2 max-w-2xl leading-7 text-gray-400">Move your reusable transformations into <code>issue-tools.mjs</code>. Keep runtime data in <code>issue-data.mjs</code>; import both into <code>main.mjs</code> and run the same program again.</p>
          <p class="mt-4 max-w-2xl text-sm leading-7 text-gray-400">Now intentionally misspell one imported name or one import path. Run it, read the actual module error, then restore the working relationship. Record what kind of mismatch the runtime reported.</p>
          <label class="mt-5 block text-sm font-semibold text-gray-200">What failed, and what evidence told you it was a module problem rather than a transformation problem?<textarea required data-b01-draft="module-error" class="mt-3 min-h-24 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-3 text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none"></textarea></label>
          <label class="mt-4 flex items-start gap-3 text-sm leading-6 text-gray-300"><input required type="checkbox" data-b01-step="modules" class="mt-1 h-4 w-4 accent-[#b58cff]"><span>I broke and repaired an import, and the modular program runs again.</span></label>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="async-title">
          <h2 id="async-title" class="text-2xl font-bold text-gray-100">Version 4: load a preview</h2>
          <p class="mt-2 max-w-2xl leading-7 text-gray-400">Add and export <code>loadIssuePreview(path)</code> from your tools module. In <code>main.mjs</code>, call it with <code>async</code>/<code>await</code>, use the successful response below, then use its returned preview without mutating the local <code>issues</code>. Derive and print one small summary from the resulting data.</p>
          <pre class="mt-5 overflow-x-auto rounded-lg bg-[#111019] p-4 text-sm leading-6 text-[#d9c9ff]"><code>const baseUrl = 'http://127.0.0.1:8000'; // use your running DALT origin
const success = baseUrl + '/learn/fullstack/observe/async/issue-preview';</code></pre>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-gray-500">Use your actual local origin if it differs. Run DALT first. In a browser Console opened on this site, relative paths also work. After parsing, make one modest application decision too: the preview must contain an <code>issue</code> with a string <code>title</code>. That is a data-meaning check, separate from JSON syntax.</p>
          <label class="mt-5 flex items-start gap-3 text-sm leading-6 text-gray-300"><input required type="checkbox" data-b01-step="success" class="mt-1 h-4 w-4 accent-[#b58cff]"><span>I observed a successful request, parsed its JSON, and printed evidence that the local data was not mutated.</span></label>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="failure-title">
          <h2 id="failure-title" class="text-2xl font-bold text-gray-100">Versions 5–7: make failures tell you where they happened</h2>
          <p class="mt-2 max-w-2xl leading-7 text-gray-400">Before changing your loader, point it at the 404 endpoint and predict: will native <code>fetch</code> automatically enter <code>catch</code>? Run it. Inspect Network and runtime evidence before repairing anything.</p>
          <pre class="mt-5 overflow-x-auto rounded-lg bg-[#111019] p-4 text-sm leading-6 text-[#d9c9ff]"><code>const missing = baseUrl + '/learn/fullstack/observe/async/missing-issue'; // 404 JSON
const malformed = baseUrl + '/learn/fullstack/observe/async/invalid-json'; // 200, not JSON</code></pre>
          <div class="mt-5 max-w-2xl text-sm leading-7 text-gray-400"><p>Repair the loader so it makes an explicit HTTP-success decision before parsing. Then run the malformed response without removing that decision.</p><p class="mt-3">For each run, inspect Console and Network: request URL, status, response body, whether <code>fetch</code> rejected or returned a Response, whether JSON parsing rejected, and which <code>catch</code> handled it. A network failure rejects before a usable Response; an application failure is different again: JSON parsed, but it did not satisfy your small <code>issue.title</code> decision. Do not solve from a checklist alone.</p></div>
          <div class="mt-6 grid gap-5 md:grid-cols-2"><label class="block text-sm font-semibold text-gray-200">404: what happened before and after your repair?<textarea required data-b01-draft="http-evidence" class="mt-3 min-h-28 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-3 text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none"></textarea></label><label class="block text-sm font-semibold text-gray-200">Malformed JSON: which stage failed?<textarea required data-b01-draft="parse-evidence" class="mt-3 min-h-28 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-3 text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none"></textarea></label></div>
          <label class="mt-5 flex items-start gap-3 text-sm leading-6 text-gray-300"><input required type="checkbox" data-b01-step="failure" class="mt-1 h-4 w-4 accent-[#b58cff]"><span>I observed success, HTTP failure, and JSON parsing failure, and can distinguish them from a network rejection.</span></label>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="hints-title">
          <h2 id="hints-title" class="text-2xl font-bold text-gray-100">Hints and reference explanations</h2>
          <p class="mt-2 max-w-2xl text-sm leading-7 text-gray-500">Open progressively. The explanations are comparison material, not a replacement for your attempt.</p>
          <div class="mt-5 space-y-3">
            <details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Transformation hint 1 — start from the result shape</summary><p class="mt-3 text-sm leading-6 text-gray-400">Are you selecting many, locating one, transforming each, testing a condition, or accumulating one summary?</p></details>
            <details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Transformation hint 2 — name the category</summary><p class="mt-3 text-sm leading-6 text-gray-400">Selection, location, transformation, testing, and accumulation have different array operations. Choose the one whose result reads like the requirement.</p></details>
            <details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Transformation hint 3 — choose the operation</summary><p class="mt-3 text-sm leading-6 text-gray-400">Many matching items, one matching item, one output per item, an “any”/“every” answer, and one summary each have a direct operation.</p></details>
            <details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Transformation hint 4 — small implementation clue</summary><p class="mt-3 text-sm leading-6 text-gray-400">For one immutable update, transform the collection and return a copied issue only when its ID matches; leave non-matches as they are.</p></details>
            <details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Async hint 1 — identify the stage</summary><p class="mt-3 text-sm leading-6 text-gray-400">Check Network and runtime separately: did a request appear, did a Response arrive, and did parsing begin?</p></details>
            <details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Async hint 2 — separate the evidence</summary><p class="mt-3 text-sm leading-6 text-gray-400">Network can show a completed 404 response even while your current JavaScript follows its success path. Console reveals which continuation ran.</p></details>
            <details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Async hint 3 — inspect the boundary</summary><p class="mt-3 text-sm leading-6 text-gray-400">Ask whether <code>fetch</code> rejected, or whether it fulfilled with a Response whose HTTP status your code still needs to judge.</p></details>
            <details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Async hint 4 — small implementation clue</summary><p class="mt-3 text-sm leading-6 text-gray-400">After awaiting <code>fetch</code>, turn a non-OK Response into an error before awaiting <code>response.json()</code>.</p></details>
            <details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Reveal reference explanation</summary><div class="mt-3 space-y-3 text-sm leading-6 text-gray-400"><p>Spread copies only the outer object or array. A nested object remains the same reference until the update explicitly creates a new nested object for that branch.</p><p>Native fetch normally fulfills when a 404 Response arrives. A loader can treat that as failure by checking <code>response.ok</code> and throwing before parsing. With a 200 malformed response, that check passes and <code>response.json()</code> is the stage that rejects.</p></div></details>
          </div>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="recall-title">
          <h2 id="recall-title" class="text-2xl font-bold text-gray-100">Close the notes, then explain from memory</h2>
          <p class="mt-2 max-w-2xl text-sm leading-7 text-gray-500">Answer first. Reveal the comparison answers only afterward.</p>
          <div class="mt-6 space-y-5"><?php foreach (['When is filter clearer than find?', 'What does object or array spread copy?', 'Why can a nested reference remain shared after spreading?', 'What does an imported binding represent?', 'What does an async function return?', 'What is the difference between a Promise and its fulfilled value?', 'Why does HTTP 404 normally not make fetch reject?', 'At what stage can response.json() fail?', 'If Network shows no request at all, which assumption should you investigate first?'] as $index => $question): ?><label class="block text-sm font-semibold text-gray-200"><?= ($index + 1) . '. ' . htmlspecialchars($question) ?><textarea required data-b01-draft="recall-<?= $index ?>" class="mt-3 min-h-24 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-3 text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none"></textarea></label><?php endforeach; ?></div>
          <details class="mt-6 rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Reveal comparison answers</summary><ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6 text-gray-400"><li><code>filter</code> expresses zero-or-many selection; <code>find</code> expresses one match or no match.</li><li>It copies the outer container, not nested containers.</li><li>The nested object was not copied, so both outer objects still point to it.</li><li>A live binding exported by another module, made available by the explicit import relationship.</li><li>A Promise.</li><li>A Promise represents a future result; its fulfilled value is available only after settlement.</li><li>A 404 is a Response, not normally a transport rejection.</li><li>After the Response arrives, while its body is read and parsed.</li><li>First confirm the function ran and that the URL/request initiation is what you believe it is.</li></ol></details>
        </section>

        <section class="border-t border-[#2a2038] pt-10"><label class="flex items-start gap-3 rounded-lg border border-[#c4a7ff]/30 bg-[#c4a7ff]/10 p-5 text-sm leading-6 text-[#e5dcff]"><input required type="checkbox" data-b01-step="honest" class="mt-1 h-4 w-4 accent-[#b58cff]"><span>I built and modified one JavaScript program, used terminal and Network/runtime evidence, attempted the recall prompts without notes, and understand this completion is self-reported, not automatically verified.</span></label><button type="submit" class="ui-button ui-button-primary mt-6">Complete B01 and return to journey <span aria-hidden="true">→</span></button></section>
      </form>
    <?php endif; ?>
  </div>
</main>
<script>
  (function () {
    var prefix = 'dalt-b01:';
    document.querySelectorAll('[data-b01-draft]').forEach(function (field) {
      var key = prefix + field.dataset.b01Draft;
      field.value = localStorage.getItem(key) || '';
      field.addEventListener('input', function () { localStorage.setItem(key, field.value); });
    });
    document.querySelectorAll('[data-b01-step]').forEach(function (field) {
      var key = prefix + 'step-' + field.dataset.b01Step;
      field.checked = localStorage.getItem(key) === 'true';
      field.addEventListener('change', function () { localStorage.setItem(key, String(field.checked)); });
    });
  })();
</script>
<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
