<?php

declare(strict_types=1);

$__learnPath = parse_url($_SERVER['REQUEST_URI'] ?? '/learn', PHP_URL_PATH) ?: '/learn';
$__learnActive = static fn (string $path): bool => $__learnPath === $path
    || ($path === '/learn/resources' && (str_starts_with($__learnPath, '/learn/lessons/') || str_starts_with($__learnPath, '/learn/challenges/')));
?>
<header class="sticky top-0 z-50 border-b border-[#1e293b] bg-[#0a0d12]/95 text-gray-200 backdrop-blur">
  <div class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-5 py-4 sm:px-6">
    <a href="/" class="flex shrink-0 items-center gap-2 group">
      <span class="inline-block h-6 w-2 rounded bg-[#93DA97] transition-all group-hover:shadow-[0_0_10px_#93DA97]"></span>
      <span class="text-lg font-bold tracking-tight text-white">DALT.PHP</span>
    </a>
    <nav class="flex items-center gap-1 text-sm" aria-label="Learning navigation">
      <a href="/learn" class="rounded-md px-3 py-2 font-medium transition-colors <?= $__learnActive('/learn') ? 'bg-[#93DA97]/10 text-[#93DA97]' : 'text-gray-400 hover:text-white' ?>" <?= $__learnActive('/learn') ? 'aria-current="page"' : '' ?>>Dashboard</a>
      <a href="/learn/resources" class="rounded-md px-3 py-2 font-medium transition-colors <?= $__learnActive('/learn/resources') ? 'bg-[#93DA97]/10 text-[#93DA97]' : 'text-gray-400 hover:text-white' ?>" <?= $__learnActive('/learn/resources') ? 'aria-current="page"' : '' ?>>Resources</a>
      <a href="/learn/roadmap" class="rounded-md px-3 py-2 font-medium transition-colors <?= $__learnActive('/learn/roadmap') ? 'bg-[#93DA97]/10 text-[#93DA97]' : 'text-gray-400 hover:text-white' ?>" <?= $__learnActive('/learn/roadmap') ? 'aria-current="page"' : '' ?>>Roadmap</a>
    </nav>
  </div>
</header>
