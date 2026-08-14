<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="color-scheme" content="dark">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
  <title>DALT.PHP — Interactive Backend Debugging Playground</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <style>
    /* Prevent FOUC (Flash of Unstyled Content) before Vite injects Tailwind */
    /* color-scheme must be set here (and in the meta above) so the browser's own
       canvas is dark too. Without it the canvas paints pure white, which shows
       through as a white flash wherever the page's background is not yet drawn. */
    :root { color-scheme: dark; }
    body, html {
      background-color: #0f1117;
      color: #d1d5db;
    }
    [v-cloak] { display: none !important; }

    /* View Transitions — override only the duration of the UA's default
       crossfade. Do not sequence old-out then new-in: any gap between the two
       leaves the page half-faded and reads as a flicker on every navigation. */
    @view-transition { navigation: auto; }

    ::view-transition-old(root),
    ::view-transition-new(root) {
      animation-duration: 160ms;
      animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    }
  </style>
  <?= dalt_vite('resources/js/app.js') ?>
</head>
<body class="min-h-screen antialiased bg-[#0f1117] text-gray-300">
<a href="#app" class="skip-link">Skip to main content</a>
<div class="min-h-screen flex flex-col">
