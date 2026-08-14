<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-4xl px-5 py-10 sm:px-6 sm:py-16">
    <a href="/learn/fullstack" class="inline-flex items-center text-sm font-medium text-gray-400 transition-colors hover:text-[#c4a7ff]"><span class="mr-2" aria-hidden="true">←</span>Back to DALT Fullstack</a>

    <header class="mt-10 max-w-3xl border-b border-[#2a2038] pb-10">
      <h1 class="text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl">Trace the system</h1>
      <p class="mt-5 text-lg leading-8 text-gray-400">This is the Part 00 Build milestone. There is no new theory here: reconstruct the system from predictions, real browser evidence, and your own explanation.</p>
      <p class="mt-4 text-sm leading-6 text-[#d9c9ff]">You are not being graded on diagram syntax. A clear text trace is enough.</p>
    </header>

    <?php if ($isCompleted): ?>
      <div class="mt-10 border-y border-[#2a2038] py-6"><p class="font-semibold text-[#c4a7ff]">B00 completed</p><p class="mt-1 text-sm text-gray-400">Part 00 is complete. Part 01 — Modern JavaScript is next, but it is not available yet.</p></div>
    <?php else: ?>
      <form method="post" action="/learn/fullstack/build/b00/complete" class="mt-10 space-y-14" data-b00-form>
        <?= csrf_field() ?>
        <section aria-labelledby="predict-title">
          <div class="max-w-2xl"><h2 id="predict-title" class="text-2xl font-bold text-gray-100">1. Predict before you inspect</h2><p class="mt-2 leading-7 text-gray-400">Before opening DevTools, predict the requests, initiator, navigation, and likely representation for each action.</p></div>
          <div class="mt-6 grid gap-5 md:grid-cols-3">
            <?php foreach ([['load', 'Load the observation page'], ['traditional', 'Submit the ordinary form'], ['javascript', 'Submit with JavaScript']] as [$key, $label]): ?>
              <label class="block rounded-xl border border-[#2a2038] bg-[#111019] p-5"><span class="font-semibold text-gray-200"><?= htmlspecialchars($label) ?></span><textarea required data-b00-draft="prediction-<?= $key ?>" class="mt-4 min-h-36 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-3 text-sm leading-6 text-gray-100 placeholder:text-gray-600 focus:border-[#c4a7ff] focus:outline-none" placeholder="What do you think will happen?"></textarea></label>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="observe-title">
          <div class="max-w-2xl"><h2 id="observe-title" class="text-2xl font-bold text-gray-100">2. Observe the evidence</h2><p class="mt-2 leading-7 text-gray-400">Open <a href="/learn/fullstack/observe/forms" class="font-semibold text-[#c4a7ff] hover:text-[#d9c9ff]">the observation fixture</a> in another tab. Keep Network open; preserve the log for the ordinary form if your browser offers it.</p></div>
          <ol class="mt-6 space-y-3 text-sm leading-6 text-gray-400"><li><span class="font-semibold text-gray-200">Page load:</span> find the document and the stylesheet or script resources it needs. This fixture does not fetch JSON until you use the JavaScript-controlled form.</li><li><span class="font-semibold text-gray-200">Ordinary form:</span> inspect the POST, its 303 response, then the GET for the new document.</li><li><span class="font-semibold text-gray-200">JavaScript form:</span> inspect the fetch POST, JSON Content-Type and visible update without a new document.</li></ol>
          <label class="mt-6 flex items-start gap-3 rounded-lg bg-[#171220] p-4 text-sm text-gray-300"><input required type="checkbox" data-b00-step="observed" class="mt-1 h-4 w-4 accent-[#b58cff]"><span>I inspected method, URL, status, Content-Type, bodies where relevant, initiator, and whether a new document was requested.</span></label>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="trace-title">
          <div class="max-w-2xl"><h2 id="trace-title" class="text-2xl font-bold text-gray-100">3. Reconstruct what happened</h2><p class="mt-2 leading-7 text-gray-400">Use short lines, arrows, or prose. Include the user action, initiator, method, server response, representation, and what the browser did next.</p></div>
          <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <label class="block text-sm font-semibold text-gray-200">Page load<textarea required data-b00-draft="trace-load" class="mt-3 min-h-52 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-4 font-mono text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none" placeholder="Browser navigation → GET …"></textarea></label>
            <label class="block text-sm font-semibold text-gray-200">Ordinary form<textarea required data-b00-draft="trace-traditional" class="mt-3 min-h-52 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-4 font-mono text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none" placeholder="User submit → browser → POST …"></textarea></label>
          </div>
          <label class="mt-6 block text-sm font-semibold text-gray-200">JavaScript-controlled interaction<textarea required data-b00-draft="trace-javascript" class="mt-3 min-h-52 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-4 font-mono text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none" placeholder="User submit → JavaScript handler → …"></textarea></label>
          <label class="mt-6 block text-sm font-semibold text-gray-200">State boundary<textarea required data-b00-draft="state-boundary" class="mt-3 min-h-32 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-4 text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none" placeholder="What is visible only in the browser? What did this fixture persist on the server? What would a database change mean?"></textarea></label>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="recall-title">
          <h2 id="recall-title" class="text-2xl font-bold text-gray-100">4. Close DevTools, then recall</h2><p class="mt-2 max-w-2xl leading-7 text-gray-400">Close DevTools. From memory, trace the ordinary form. Then reopen DevTools and correct it. The correction is part of the work.</p>
          <textarea required data-b00-draft="memory-trace" class="mt-6 min-h-44 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-4 font-mono text-sm leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none" placeholder="From memory: user submits …"></textarea>
          <label class="mt-4 flex items-start gap-3 text-sm text-gray-300"><input required type="checkbox" data-b00-step="corrected" class="mt-1 h-4 w-4 accent-[#b58cff]"><span>I reopened DevTools and compared or corrected my reconstruction.</span></label>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="explain-title">
          <h2 id="explain-title" class="text-2xl font-bold text-gray-100">5. Explain after the build</h2><p class="mt-2 max-w-2xl leading-7 text-gray-400">Answer briefly in your own words. These prompts test the model, not phrasing.</p>
          <div class="mt-6 space-y-5"><?php foreach (['Why can typing one URL result in several HTTP requests?', 'What differs between navigation and a request initiated by JavaScript?', 'What normally happens when an ordinary HTML form submits?', 'Why can JavaScript update the page without a new HTML document?', 'If something disappears after refresh, what does that suggest about its state?'] as $index => $question): ?><label class="block text-sm font-semibold text-gray-200"><?= ($index + 1) . '. ' . htmlspecialchars($question) ?><textarea required data-b00-draft="explain-<?= $index ?>" class="mt-3 min-h-24 w-full rounded-lg border border-gray-700 bg-[#0a0d12] p-3 text-sm font-normal leading-6 text-gray-100 focus:border-[#c4a7ff] focus:outline-none"></textarea></label><?php endforeach; ?></div>
        </section>

        <section class="border-t border-[#2a2038] pt-10" aria-labelledby="help-title">
          <h2 id="help-title" class="text-2xl font-bold text-gray-100">Hints and comparison</h2>
          <div class="mt-5 space-y-3"><details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Hint 1</summary><p class="mt-3 text-sm leading-6 text-gray-400">Start by asking which action initiated the request.</p></details><details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Hint 2</summary><p class="mt-3 text-sm leading-6 text-gray-400">Separate document navigation from requests initiated by page resources or JavaScript.</p></details><details class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><summary class="cursor-pointer font-semibold text-gray-200">Hint 3</summary><p class="mt-3 text-sm leading-6 text-gray-400">For each exchange: method → destination → status → representation → next browser action.</p></details><div class="rounded-lg border border-[#2a2038] bg-[#111019] p-4"><button type="button" data-b00-reference-button disabled aria-describedby="reference-note" class="font-semibold text-gray-200 disabled:cursor-not-allowed disabled:text-gray-500">Show the reference explanation</button><p id="reference-note" class="mt-3 text-sm leading-6 text-gray-400">Write a reconstruction for page load, the ordinary form, and the JavaScript-controlled interaction first. Then use this as a comparison, not a template.</p><details data-b00-reference hidden class="mt-4"><summary class="cursor-pointer font-semibold text-gray-200">Reference explanation</summary><div class="mt-3 space-y-3 text-sm leading-6 text-gray-400"><p>A page navigation asks the server for HTML. Parsing that document can cause separate requests for styles and scripts. This fixture’s script then handles the JavaScript form and fetches JSON only after the learner submits it.</p><p>The ordinary form is browser-default behavior: submit → browser POST → server 303 redirect → browser GET → new HTML document and navigation.</p><p>The JavaScript form handler calls <code>preventDefault()</code>, sends a JSON POST with <code>fetch</code>, reads JSON, and changes text in the document that is already loaded. The fixture does not persist preview titles; a database would be a separate server-side persistence layer.</p></div></details></div></div>
        </section>

        <section class="border-t border-[#2a2038] pt-10"><label class="flex items-start gap-3 rounded-lg border border-[#c4a7ff]/30 bg-[#c4a7ff]/10 p-5 text-sm leading-6 text-[#e5dcff]"><input required type="checkbox" data-b00-step="honest" class="mt-1 h-4 w-4 accent-[#b58cff]"><span>I made predictions, inspected the real fixture, reconstructed the interactions, recalled one trace with DevTools closed, compared/corrected it, and attempted the explanation prompts. This milestone is self-reported, not automatically verified.</span></label><button type="submit" class="ui-button ui-button-primary mt-6">Complete B00 and return to journey <span aria-hidden="true">→</span></button></section>
      </form>
    <?php endif; ?>
  </div>
</main>
<script>
  (function () {
    var prefix = 'dalt-b00:';
    document.querySelectorAll('[data-b00-draft]').forEach(function (field) {
      var key = prefix + field.dataset.b00Draft;
      field.value = localStorage.getItem(key) || '';
      field.addEventListener('input', function () { localStorage.setItem(key, field.value); });
    });
    document.querySelectorAll('[data-b00-step]').forEach(function (field) {
      var key = prefix + 'step-' + field.dataset.b00Step;
      field.checked = localStorage.getItem(key) === 'true';
      field.addEventListener('change', function () { localStorage.setItem(key, String(field.checked)); });
    });
    var referenceButton = document.querySelector('[data-b00-reference-button]');
    var reference = document.querySelector('[data-b00-reference]');
    var reconstructionFields = ['trace-load', 'trace-traditional', 'trace-javascript'].map(function (key) {
      return document.querySelector('[data-b00-draft="' + key + '"]');
    });
    var updateReferenceAvailability = function () {
      var attempted = reconstructionFields.every(function (field) { return field && field.value.trim() !== ''; });
      referenceButton.disabled = !attempted;
    };
    reconstructionFields.forEach(function (field) { if (field) field.addEventListener('input', updateReferenceAvailability); });
    referenceButton.addEventListener('click', function () { reference.hidden = false; reference.open = true; reference.scrollIntoView({block: 'nearest'}); });
    updateReferenceAvailability();
  })();
</script>
<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
