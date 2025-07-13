<?php require('partials/head.php') ?>
<?php require('partials/nav.php') ?>

<main class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-6 py-20">
        <div class="text-center space-y-6">
            <h1 class="text-5xl lg:text-6xl font-bold text-white leading-tight">
                Welcome to <span class="text-indigo-400">DALTPHP</span>
            </h1>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                A modern PHP framework combining the best of DaisyUI, Alpine.js, Laravel, and TailwindCSS
            </p>
            <?php if($_SESSION['user'] ?? false) : ?>
                <div class="bg-indigo-900/20 border border-indigo-700 rounded-2xl p-4 max-w-md mx-auto">
                    <p class="text-indigo-300">
                        Hello <strong><?= $_SESSION['user']['email'] ?></strong>! 
                        <br>Ready to build something amazing?
                    </p>
                </div>
            <?php else : ?>
                <div class="flex justify-center space-x-4">
                    <a href="/register" class="bg-indigo-500 hover:bg-indigo-600 text-white px-8 py-3 rounded-full font-medium transition-all duration-200">
                        Get Started
                    </a>
                    <a href="/login" class="bg-gray-800 text-gray-300 px-8 py-3 rounded-full font-medium hover:bg-gray-700 transition-all duration-200">
                        Sign In
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- GitHub Link -->
            <div class="pt-8">
                <a href="https://github.com/yourusername/daltphp" target="_blank" class="inline-flex items-center space-x-2 text-gray-400 hover:text-indigo-400 transition-all duration-200">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    <span class="text-sm font-medium">View on GitHub</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Tools Showcase -->
    <section class="max-w-7xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white mb-4">Powered by Modern Tools</h2>
            <p class="text-gray-300">DALTPHP combines the best frontend and backend technologies</p>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
            <a href="https://daisyui.com" target="_blank" class="group bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-xl shadow-gray-900/20 transition-all duration-300 text-center border border-gray-700">
                <div class="w-16 h-16 mx-auto mb-4 bg-green-900/20 rounded-full flex items-center justify-center group-hover:bg-green-900/40 transition-colors">
                    <svg class="w-8 h-8 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">DaisyUI</h3>
                <p class="text-sm text-gray-400 mt-2">Beautiful components</p>
            </a>
            
            <a href="https://alpinejs.dev" target="_blank" class="group bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-xl shadow-gray-900/20 transition-all duration-300 text-center border border-gray-700">
                <div class="w-16 h-16 mx-auto mb-4 bg-blue-900/20 rounded-full flex items-center justify-center group-hover:bg-blue-900/40 transition-colors">
                    <svg class="w-8 h-8 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">Alpine.js</h3>
                <p class="text-sm text-gray-400 mt-2">Reactive magic</p>
            </a>
            
            <a href="https://laravel.com" target="_blank" class="group bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-xl shadow-gray-900/20 transition-all duration-300 text-center border border-gray-700">
                <div class="w-16 h-16 mx-auto mb-4 bg-red-900/20 rounded-full flex items-center justify-center group-hover:bg-red-900/40 transition-colors">
                    <svg class="w-8 h-8 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7v10c0 5.55 3.84 9.95 9 11 5.16-1.05 9-5.45 9-11V7l-10-5z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">Laravel</h3>
                <p class="text-sm text-gray-400 mt-2">Elegant architecture</p>
            </a>
            
            <a href="https://tailwindcss.com" target="_blank" class="group bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-xl shadow-gray-900/20 transition-all duration-300 text-center border border-gray-700">
                <div class="w-16 h-16 mx-auto mb-4 bg-purple-900/20 rounded-full flex items-center justify-center group-hover:bg-purple-900/40 transition-colors">
                    <svg class="w-8 h-8 text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">TailwindCSS</h3>
                <p class="text-sm text-gray-400 mt-2">Utility-first styling</p>
            </a>
        </div>
    </section>

    <!-- Interactive Demo -->
    <section class="max-w-7xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white mb-4">Experience the Power</h2>
            <p class="text-gray-300">See DaisyUI and Alpine.js working together seamlessly</p>
        </div>

        <div class="bg-gray-800 rounded-3xl shadow-lg shadow-gray-900/20 p-8 max-w-4xl mx-auto border border-gray-700">
            <div x-data="{ 
                activeTab: 'counter',
                count: 0,
                tasks: ['Build awesome apps', 'Learn new skills', 'Deploy to production'],
                newTask: '',
                showNotification: false,
                theme: 'light'
            }">
                <!-- Tab Navigation - Fixed for mobile overflow -->
                <div class="flex justify-center mb-8">
                    <div class="bg-gray-700 rounded-full p-1 overflow-hidden">
                        <div class="flex flex-col sm:flex-row w-full sm:w-auto">
                            <button @click="activeTab = 'counter'" 
                                    :class="activeTab === 'counter' ? 'bg-gray-600 text-indigo-400 shadow-sm' : 'text-gray-400 hover:text-indigo-400'"
                                    class="px-4 sm:px-6 py-2 rounded-full text-sm font-medium transition-all duration-200 mb-1 sm:mb-0 sm:mr-1">
                                Counter Demo
                            </button>
                            <button @click="activeTab = 'tasks'" 
                                    :class="activeTab === 'tasks' ? 'bg-gray-600 text-indigo-400 shadow-sm' : 'text-gray-400 hover:text-indigo-400'"
                                    class="px-4 sm:px-6 py-2 rounded-full text-sm font-medium transition-all duration-200 mb-1 sm:mb-0 sm:mr-1">
                                Task Manager
                            </button>
                            <button @click="activeTab = 'theme'" 
                                    :class="activeTab === 'theme' ? 'bg-gray-600 text-indigo-400 shadow-sm' : 'text-gray-400 hover:text-indigo-400'"
                                    class="px-4 sm:px-6 py-2 rounded-full text-sm font-medium transition-all duration-200">
                                Theme Toggle
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Counter Demo -->
                <div x-show="activeTab === 'counter'" x-transition class="text-center space-y-6">
                    <h3 class="text-2xl font-bold text-white">Interactive Counter</h3>
                    <div class="text-4xl sm:text-6xl font-bold text-indigo-400 py-8" x-text="count"></div>
                    <div class="flex flex-col sm:flex-row justify-center gap-4 sm:space-x-4 sm:gap-0">
                        <button @click="count = Math.max(0, count - 1)" 
                                class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-full font-medium transition-all duration-200">
                            Decrease
                        </button>
                        <button @click="count = 0" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-full font-medium transition-all duration-200">
                            Reset
                        </button>
                        <button @click="count++" 
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-full font-medium transition-all duration-200">
                            Increase
                        </button>
                    </div>
                </div>

                <!-- Task Manager Demo -->
                <div x-show="activeTab === 'tasks'" x-transition class="space-y-6">
                    <h3 class="text-2xl font-bold text-white text-center">Task Manager</h3>
                    
                    <div class="flex flex-col sm:flex-row gap-4 sm:space-x-4 sm:gap-0">
                        <input x-model="newTask" 
                               @keyup.enter="if(newTask.trim()) { tasks.push(newTask.trim()); newTask = ''; showNotification = true; setTimeout(() => showNotification = false, 2000); }"
                               placeholder="Add a new task..." 
                               class="flex-1 px-4 py-3 border border-gray-600 bg-gray-700 text-white rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <button @click="if(newTask.trim()) { tasks.push(newTask.trim()); newTask = ''; showNotification = true; setTimeout(() => showNotification = false, 2000); }"
                                class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-3 rounded-full font-medium transition-all duration-200">
                            Add Task
                        </button>
                    </div>
                    
                    <div x-show="showNotification" x-transition class="bg-green-900/20 border border-green-700 text-green-300 px-4 py-3 rounded-xl">
                        Task added successfully! 🎉
                    </div>
                    
                    <div class="space-y-3">
                        <template x-for="(task, index) in tasks" :key="index">
                            <div class="flex items-center justify-between bg-gray-700 p-4 rounded-xl">
                                <span x-text="task" class="text-white"></span>
                                <button @click="tasks.splice(index, 1)" 
                                        class="text-red-400 hover:text-red-300 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Theme Toggle Demo -->
                <div x-show="activeTab === 'theme'" x-transition class="text-center space-y-6">
                    <h3 class="text-2xl font-bold text-white">Theme Switcher</h3>
                    <div :class="theme === 'dark' ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'" 
                         class="p-8 rounded-2xl border border-gray-600 transition-all duration-300">
                        <h4 class="text-xl font-semibold mb-4">Current Theme: <span x-text="theme"></span></h4>
                        <p class="mb-6">This card changes appearance based on the selected theme.</p>
                        <div class="flex flex-col sm:flex-row justify-center gap-4 sm:space-x-4 sm:gap-0">
                            <button @click="theme = 'light'" 
                                    :class="theme === 'light' ? 'bg-yellow-400 text-gray-900' : 'bg-gray-600 text-gray-300'"
                                    class="px-6 py-3 rounded-full font-medium transition-all duration-200">
                                Light Mode
                            </button>
                            <button @click="theme = 'dark'" 
                                    :class="theme === 'dark' ? 'bg-gray-700 text-white' : 'bg-gray-600 text-gray-300'"
                                    class="px-6 py-3 rounded-full font-medium transition-all duration-200">
                                Dark Mode
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="max-w-7xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white mb-4">Why Choose DALTPHP?</h2>
            <p class="text-gray-300">Built for modern web development with developer experience in mind</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center space-y-4">
                <div class="w-16 h-16 bg-indigo-900/20 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white">Lightning Fast</h3>
                <p class="text-gray-400">Optimized for performance with minimal overhead and smart caching</p>
            </div>
            
            <div class="text-center space-y-4">
                <div class="w-16 h-16 bg-green-900/20 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white">Developer Friendly</h3>
                <p class="text-gray-400">Intuitive API design with excellent documentation and tooling</p>
            </div>
            
            <div class="text-center space-y-4">
                <div class="w-16 h-16 bg-purple-900/20 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white">Modern UI</h3>
                <p class="text-gray-400">Beautiful components with DaisyUI and reactive interactions with Alpine.js</p>
            </div>
        </div>
    </section>
</main>



<?php require('partials/footer.php') ?>