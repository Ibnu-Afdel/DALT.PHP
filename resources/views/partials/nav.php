<nav class="py-4 px-6 bg-gradient-to-r from-gray-900 to-gray-800 transition-colors duration-300">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <!-- Modern Framework Icon -->
            <svg class="w-8 h-8 text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 7v10c0 5.55 3.84 9.95 9 11 5.16-1.05 9-5.45 9-11V7l-10-5z"/>
                <path d="M12 4.5L4.5 8.5v8.5c0 4.5 3.5 8 7.5 8s7.5-3.5 7.5-8V8.5L12 4.5z" fill="white"/>
            </svg>
            <a href="/" class="text-2xl font-bold text-white">DALTPHP</a>
        </div>
        
        <!-- Sticky Navigation Container -->
        <div class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 hidden lg:block" id="stickyNav">
            <div class="bg-gray-800/80 backdrop-blur-md rounded-full px-2 py-1 shadow-lg border border-gray-700">
                <div class="flex items-center space-x-1">
                    <a href="/" class="<?= urlIs('/') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:text-indigo-400' ?> px-4 py-2 rounded-full text-sm font-medium transition-all duration-200">Home</a>
                    <a href="/about" class="<?= urlIs('/about') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:text-indigo-400' ?> px-4 py-2 rounded-full text-sm font-medium transition-all duration-200">About</a>
                    <a href="/contact" class="<?= urlIs('/contact') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:text-indigo-400' ?> px-4 py-2 rounded-full text-sm font-medium transition-all duration-200">Contact</a>
                </div>
            </div>
        </div>
        
        <!-- Desktop Navigation (Original) -->
        <div class="hidden lg:flex items-center space-x-1 bg-gray-800/50 backdrop-blur-sm rounded-full px-2 py-1" id="originalNav">
            <a href="/" class="<?= urlIs('/') ? 'bg-gray-700 text-indigo-400 shadow-sm' : 'text-gray-300 hover:text-indigo-400' ?> px-4 py-2 rounded-full text-sm font-medium transition-all duration-200">Home</a>
            <a href="/about" class="<?= urlIs('/about') ? 'bg-gray-700 text-indigo-400 shadow-sm' : 'text-gray-300 hover:text-indigo-400' ?> px-4 py-2 rounded-full text-sm font-medium transition-all duration-200">About</a>
            <a href="/contact" class="<?= urlIs('/contact') ? 'bg-gray-700 text-indigo-400 shadow-sm' : 'text-gray-300 hover:text-indigo-400' ?> px-4 py-2 rounded-full text-sm font-medium transition-all duration-200">Contact</a>
        </div>
        
        <!-- Desktop Auth Section -->
        <div class="hidden lg:flex items-center space-x-3">
            <?php if($_SESSION['user'] ?? false) : ?>
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-indigo-900 text-indigo-400 rounded-full flex items-center justify-center text-sm font-medium">
                            <?= strtoupper(substr($_SESSION['user']['email'], 0, 1)) ?>
                        </div>
                        <span class="text-sm text-gray-300 hidden xl:block"><?= $_SESSION['user']['email'] ?></span>
                    </div>
                    <form action="/session" method="POST">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="bg-red-900/20 text-red-400 hover:bg-red-900/40 px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-200">
                            Logout
                        </button>
                    </form>
                </div>
            <?php else : ?>
                <div class="flex items-center space-x-2">
                    <a href="/login" class="text-gray-300 hover:text-indigo-400 px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-200">Login</a>
                    <a href="/register" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200">Register</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Mobile Menu Button (Top Right) -->
        <div class="lg:hidden">
            <button x-data @click="$dispatch('toggle-mobile-menu')" class="p-2 rounded-md text-gray-300 hover:text-indigo-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <div x-data="{ open: false }" @toggle-mobile-menu.window="open = !open" x-show="open" x-transition class="lg:hidden mt-4">
        <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl p-4 space-y-4 border border-gray-700">
            <!-- Navigation Links -->
            <div class="space-y-2">
                <a href="/" class="<?= urlIs('/') ? 'bg-indigo-900/20 text-indigo-400' : 'text-gray-300 hover:text-indigo-400' ?> block px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200">Home</a>
                <a href="/about" class="<?= urlIs('/about') ? 'bg-indigo-900/20 text-indigo-400' : 'text-gray-300 hover:text-indigo-400' ?> block px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200">About</a>
                <a href="/contact" class="<?= urlIs('/contact') ? 'bg-indigo-900/20 text-indigo-400' : 'text-gray-300 hover:text-indigo-400' ?> block px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200">Contact</a>
            </div>
            
            <!-- Divider -->
            <div class="border-t border-gray-600"></div>
            
            <!-- Auth Section -->
            <?php if($_SESSION['user'] ?? false) : ?>
                <div class="space-y-3">
                    <div class="flex items-center space-x-3 px-4 py-2 bg-gray-700 rounded-xl">
                        <div class="w-8 h-8 bg-indigo-900 text-indigo-400 rounded-full flex items-center justify-center text-sm font-medium">
                            <?= strtoupper(substr($_SESSION['user']['email'], 0, 1)) ?>
                        </div>
                        <span class="text-sm text-gray-300 flex-1"><?= $_SESSION['user']['email'] ?></span>
                    </div>
                    <form action="/session" method="POST">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="w-full bg-red-900/20 text-red-400 hover:bg-red-900/40 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200">
                            Logout
                        </button>
                    </form>
                </div>
            <?php else : ?>
                <div class="space-y-2">
                    <a href="/login" class="block w-full text-center bg-gray-700 text-gray-300 hover:text-indigo-400 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200">Login</a>
                    <a href="/register" class="block w-full text-center bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// Sticky Navigation Script
window.addEventListener('scroll', function() {
    const originalNav = document.getElementById('originalNav');
    const stickyNav = document.getElementById('stickyNav');
    const scrollPosition = window.scrollY;
    
    if (scrollPosition > 100) {
        originalNav.style.opacity = '0';
        originalNav.style.pointerEvents = 'none';
        stickyNav.style.opacity = '1';
        stickyNav.style.pointerEvents = 'auto';
    } else {
        originalNav.style.opacity = '1';
        originalNav.style.pointerEvents = 'auto';
        stickyNav.style.opacity = '0';
        stickyNav.style.pointerEvents = 'none';
    }
});

// Initialize sticky nav as hidden
document.addEventListener('DOMContentLoaded', function() {
    const stickyNav = document.getElementById('stickyNav');
    stickyNav.style.opacity = '0';
    stickyNav.style.pointerEvents = 'none';
});
</script>
