<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="view-transition" content="same-origin">
  <title>DALT.PHP — Interactive Backend Debugging Playground</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <style>
    /* Prevent FOUC (Flash of Unstyled Content) before Vite injects Tailwind */
    body, html {
      background-color: #0f1117;
      color: #d1d5db;
    }
    [v-cloak] { display: none !important; }
    
    /* View Transitions */
    @view-transition { navigation: auto; }
    
    ::view-transition-old(root) {
      animation: 90ms cubic-bezier(0.4, 0, 1, 1) both fade-out;
    }
    ::view-transition-new(root) {
      animation: 210ms cubic-bezier(0, 0, 0.2, 1) 90ms both fade-in;
    }
    @keyframes fade-out { to { opacity: 0; } }
    @keyframes fade-in { from { opacity: 0; } }
  </style>
  <?= vite('.dalt/resources/js/app.js') ?>
</head>
<body class="min-h-screen antialiased bg-[#0f1117] text-gray-300">
<div class="min-h-screen flex flex-col">
