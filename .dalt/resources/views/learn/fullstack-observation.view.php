<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/learn-nav.php') ?>

<main class="min-h-[calc(100vh-8rem)] bg-[#0a0d12] text-gray-300" id="app" tabindex="-1">
  <div class="mx-auto max-w-3xl px-5 py-10 sm:px-6 sm:py-16">
    <a href="/learn/lessons/21-fs00-2-forms-json-and-spa" class="inline-flex items-center text-sm font-medium text-gray-500 transition-colors hover:text-[#c4a7ff]"><span class="mr-2" aria-hidden="true">←</span>Back to FS00.2</a>

    <header class="mt-10 border-b border-[#2a2038] pb-10">
      <p class="font-mono text-xs font-semibold uppercase tracking-[0.16em] text-[#c4a7ff]">Part 00 observation fixture</p>
      <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-50 sm:text-5xl">Two ways to submit</h1>
      <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-400">Use this small page with your Network panel open. It does not save preview titles anywhere.</p>
    </header>

    <?php if ($submittedTraditional): ?>
      <p class="mt-8 rounded-lg border border-[#c4a7ff]/30 bg-[#c4a7ff]/10 px-4 py-3 text-sm text-[#d9c9ff]">The ordinary form navigated here after its redirect. This message is only evidence for the observation.</p>
    <?php endif; ?>

    <section class="mt-10 rounded-xl border border-[#2a2038] bg-[#111019] p-6" aria-labelledby="ordinary-title">
      <p class="font-mono text-xs uppercase tracking-wider text-[#c4a7ff]">A · Browser default</p>
      <h2 id="ordinary-title" class="mt-2 text-xl font-bold text-gray-100">Ordinary HTML form</h2>
      <form class="mt-5 space-y-4" method="post" action="/learn/fullstack/observe/forms/traditional">
        <label class="block text-sm font-medium text-gray-300" for="traditional-title">Preview title<input id="traditional-title" name="title" required class="mt-2 block w-full rounded-lg border border-gray-700 bg-[#0a0d12] px-3 py-2 text-gray-100" value="Browser-created request"></label>
        <button class="ui-button ui-button-primary" type="submit">Submit ordinary form</button>
      </form>
    </section>

    <section class="mt-6 rounded-xl border border-[#2a2038] bg-[#111019] p-6" aria-labelledby="javascript-title">
      <p class="font-mono text-xs uppercase tracking-wider text-[#c4a7ff]">B · JavaScript-controlled</p>
      <h2 id="javascript-title" class="mt-2 text-xl font-bold text-gray-100">JavaScript-controlled form</h2>
      <form id="json-preview-form" class="mt-5 space-y-4" method="post" action="/learn/fullstack/observe/forms/json">
        <label class="block text-sm font-medium text-gray-300" for="json-title">Preview title<input id="json-title" name="title" required class="mt-2 block w-full rounded-lg border border-gray-700 bg-[#0a0d12] px-3 py-2 text-gray-100" value="JavaScript-created request"></label>
        <button class="ui-button ui-button-primary" type="submit">Submit with JavaScript</button>
      </form>
      <p id="json-preview-status" class="mt-5 text-sm text-gray-500" aria-live="polite">No JavaScript-controlled request has been sent yet.</p>
    </section>
  </div>
</main>

<script>
  document.getElementById('json-preview-form').addEventListener('submit', function (event) {
    event.preventDefault();
    var form = event.currentTarget;
    var status = document.getElementById('json-preview-status');
    var title = new FormData(form).get('title');

    fetch(form.action, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({title: title})
    })
      .then(function (response) { return response.json(); })
      .then(function (data) { status.textContent = data.message + ' The current document stayed loaded.'; });
  });
</script>

<?php require base_path('.dalt/resources/views/layouts/learn-end.php') ?>
