<header class="bg-gradient-to-r from-red-500 to-pink-500 shadow-lg">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold tracking-tight text-white text-center"> 
            <?= $_SESSION['name'] ?? $heading ?> 
        </h1>
        <p class="text-red-100 text-center mt-4 text-lg">
            Built with DaisyUI • Alpine.js • Laravel • TailwindCSS
        </p>
    </div>
</header>
