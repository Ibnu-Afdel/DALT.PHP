<nav class="py-4 px-6 bg-gradient-to-r from-gray-900 to-gray-800 transition-colors duration-300">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <svg class="w-8 h-8 text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 7v10c0 5.55 3.84 9.95 9 11 5.16-1.05 9-5.45 9-11V7l-10-5z"/>
                <path d="M12 4.5L4.5 8.5v8.5c0 4.5 3.5 8 7.5 8s7.5-3.5 7.5-8V8.5L12 4.5z" fill="white"/>
            </svg>
            <a href="/" class="text-2xl font-bold text-white">DALTPHP</a>
        </div>

        <div class="hidden lg:flex items-center space-x-1 bg-gray-800/50 backdrop-blur-sm rounded-full px-2 py-1">
            <a href="/" class="<?= urlIs('/') ? 'bg-gray-700 text-indigo-400 shadow-sm' : 'text-gray-300 hover:text-indigo-400' ?> px-4 py-2 rounded-full text-sm font-medium transition-all duration-200">Home</a>
        </div>

        <div class="lg:hidden">
            <button x-data @click="$dispatch('toggle-mobile-menu')" class="p-2 rounded-md text-gray-300 hover:text-indigo-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>

    <div x-data="{ open: false }" @toggle-mobile-menu.window="open = !open" x-show="open" x-transition class="lg:hidden mt-4">
        <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl p-4 border border-gray-700">
            <a href="/" class="<?= urlIs('/') ? 'bg-indigo-900/20 text-indigo-400' : 'text-gray-300 hover:text-indigo-400' ?> block px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200">Home</a>
        </div>
    </div>
</nav>
