<?php require('partials/head.php') ?>
<?php require('partials/nav.php') ?>
<main class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
    <!-- Hero Section -->
    <section class="max-w-4xl mx-auto px-6 py-16 text-center space-y-8">
        <h1 class="text-4xl lg:text-5xl font-bold text-white">About <span class="text-indigo-400">DALTPHP</span></h1>
        <p class="text-lg text-gray-300 max-w-2xl mx-auto">
            DALTPHP combines Laravel migrations with TailwindCSS, DaisyUI, and Alpine.js for modern web development.
        </p>
        <div class="bg-indigo-900/20 border border-indigo-700 rounded-xl p-6 max-w-2xl mx-auto">
            <h2 class="text-xl font-semibold text-indigo-300 mb-3">Our Mission</h2>
            <p class="text-gray-300 text-sm leading-relaxed">
                To provide developers with a lightweight, fast framework that accelerates web development 
                while maintaining code quality and best practices.
            </p>
        </div>
    </section>
        
    <!-- Interactive Showcase Section -->
    <section class="max-w-4xl mx-auto px-6 py-12">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white mb-3">Interactive Components</h2>
            <p class="text-gray-300 text-sm">Experience Alpine.js and DaisyUI working together</p>
        </div>

        <!-- Individual Component Sections -->
        <div class="space-y-8">
            <!-- Counter Component -->
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                <div x-data="{ count: 0 }" class="text-center space-y-4">
                    <div class="space-y-2">
                        <h3 class="text-xl font-bold text-white">🔢 Reactive Counter</h3>
                        <p class="text-gray-400 text-sm">Demonstrates Alpine.js reactivity with real-time state management</p>
                    </div>
                    <div class="text-3xl font-bold text-indigo-400 py-4" x-text="count"></div>
                    <div class="flex flex-col sm:flex-row justify-center gap-3 sm:space-x-3 sm:gap-0">
                        <button @click="count = Math.max(0, count - 1)" 
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                            Decrease
                        </button>
                        <button @click="count = 0" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                            Reset
                        </button>
                        <button @click="count++" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                            Increase
                        </button>
                    </div>
                    <div class="bg-gray-700 p-3 rounded-lg mt-4">
                        <h4 class="text-xs font-semibold text-white mb-1">How it works:</h4>
                        <p class="text-xs text-gray-300">Using Alpine.js's <code class="bg-gray-600 px-1 py-0.5 rounded text-indigo-300 text-xs">x-data</code> directive to create reactive state, <code class="bg-gray-600 px-1 py-0.5 rounded text-indigo-300 text-xs">x-text</code> for data binding, and <code class="bg-gray-600 px-1 py-0.5 rounded text-indigo-300 text-xs">@click</code> for event handling.</p>
                    </div>
                </div>
            </div>

            <!-- Task Manager Component -->
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                <div x-data="{ 
                    tasks: ['Build awesome apps', 'Learn new skills', 'Deploy to production'],
                    newTask: '',
                    showNotification: false
                }" class="space-y-4">
                    <div class="text-center space-y-2">
                        <h3 class="text-xl font-bold text-white">📋 Dynamic Task Manager</h3>
                        <p class="text-gray-400 text-sm">Shows dynamic list manipulation and form handling with Alpine.js</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3 sm:space-x-3 sm:gap-0">
                        <input x-model="newTask" 
                               @keyup.enter="if(newTask.trim()) { tasks.push(newTask.trim()); newTask = ''; showNotification = true; setTimeout(() => showNotification = false, 2000); }"
                               placeholder="Add a new task..." 
                               class="flex-1 px-3 py-2 border border-gray-600 bg-gray-700 text-white rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <button @click="if(newTask.trim()) { tasks.push(newTask.trim()); newTask = ''; showNotification = true; setTimeout(() => showNotification = false, 2000); }"
                                class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                            Add Task
                        </button>
                    </div>
                    
                    <div x-show="showNotification" x-transition class="bg-green-900/20 border border-green-700 text-green-300 px-3 py-2 rounded-lg text-sm">
                        Task added successfully! 🎉
                    </div>
                    
                    <div class="space-y-2">
                        <template x-for="(task, index) in tasks" :key="index">
                            <div class="flex items-center justify-between bg-gray-700 p-3 rounded-lg">
                                <span x-text="task" class="text-white text-sm"></span>
                                <button @click="tasks.splice(index, 1)" 
                                        class="text-red-400 hover:text-red-300 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    
                    <div class="bg-gray-700 p-3 rounded-lg">
                        <h4 class="text-xs font-semibold text-white mb-1">Advanced Features:</h4>
                        <p class="text-xs text-gray-300">Uses <code class="bg-gray-600 px-1 py-0.5 rounded text-indigo-300 text-xs">x-for</code> for list rendering, <code class="bg-gray-600 px-1 py-0.5 rounded text-indigo-300 text-xs">x-model</code> for two-way data binding, and <code class="bg-gray-600 px-1 py-0.5 rounded text-indigo-300 text-xs">x-show</code> with transitions.</p>
                    </div>
                </div>
            </div>

            <!-- Theme Switcher Component -->
            <div class="bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-700">
                <div x-data="{ theme: 'light' }" class="text-center space-y-4">
                    <div class="space-y-2">
                        <h3 class="text-xl font-bold text-white">🎨 Theme Switcher</h3>
                        <p class="text-gray-400 text-sm">Illustrates conditional styling and state-based UI changes</p>
                    </div>
                    <div :class="theme === 'dark' ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'" 
                         class="p-6 rounded-xl border border-gray-600 transition-all duration-300">
                        <h4 class="text-lg font-semibold mb-3">Current Theme: <span x-text="theme"></span></h4>
                        <p class="mb-4 text-sm">This card changes appearance based on the selected theme using Alpine.js reactivity.</p>
                        <div class="flex flex-col sm:flex-row justify-center gap-3 sm:space-x-3 sm:gap-0">
                            <button @click="theme = 'light'" 
                                    :class="theme === 'light' ? 'bg-yellow-400 text-gray-900' : 'bg-gray-600 text-gray-300'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                                Light Mode
                            </button>
                            <button @click="theme = 'dark'" 
                                    :class="theme === 'dark' ? 'bg-gray-700 text-white' : 'bg-gray-600 text-gray-300'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                                Dark Mode
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-gray-700 p-3 rounded-lg">
                        <h4 class="text-xs font-semibold text-white mb-1">Conditional Styling:</h4>
                        <p class="text-xs text-gray-300">Demonstrates <code class="bg-gray-600 px-1 py-0.5 rounded text-indigo-300 text-xs">:class</code> binding for conditional CSS classes, enabling dynamic theme switching with smooth transitions.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Technology Stack Section -->
    <section class="max-w-4xl mx-auto px-6 py-12">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-white mb-3">Technology Stack</h2>
            <p class="text-gray-300 text-sm">Tools that power DALTPHP</p>
        </div>
        
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <!-- Backend Tools -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 bg-red-900/20 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7v10c0 5.55 3.84 9.95 9 11 5.16-1.05 9-5.45 9-11V7l-10-5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">Backend</h3>
                </div>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li>• <strong class="text-white">Laravel Migrations:</strong> Clean database schema</li>
                    <li>• <strong class="text-white">PHP:</strong> Server-side logic</li>
                    <li>• <strong class="text-white">Routing:</strong> Clean URL handling</li>
                    <li>• <strong class="text-white">Middleware:</strong> Request filtering</li>
                </ul>
            </div>
            
            <!-- Frontend Tools -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 bg-indigo-900/20 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">Frontend</h3>
                </div>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li>• <strong class="text-white">TailwindCSS:</strong> Utility-first styling</li>
                    <li>• <strong class="text-white">DaisyUI:</strong> Component library</li>
                    <li>• <strong class="text-white">Alpine.js:</strong> Lightweight reactivity</li>
                </ul>
            </div>
        </div>
        <!-- Performance Section -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 mb-8">
            <div class="text-center">
                <h3 class="text-lg font-bold text-white mb-3">⚡ Performance</h3>
                <p class="text-gray-300 text-sm">Lightweight and fast.</p>
            </div>
        </div>
        <!-- How It Works Section -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h3 class="text-lg font-bold text-white mb-4 text-center">🛠️ How It Works</h3>
            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-white font-bold text-xs">1</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-1">Request Routing</h4>
                        <p class="text-gray-300 text-xs">Router handles incoming requests, applies middleware, and dispatches to controllers.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-white font-bold text-xs">2</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-1">Database Operations</h4>
                        <p class="text-gray-300 text-xs">Controllers interact with the database using Laravel migrations for clean schema and direct SQL queries.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-white font-bold text-xs">3</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-1">View Rendering</h4>
                        <p class="text-gray-300 text-xs">PHP templates render HTML enhanced with Alpine.js directives and styled with TailwindCSS.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-white font-bold text-xs">4</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white mb-1">Frontend Enhancement</h4>
                        <p class="text-gray-300 text-xs">Alpine.js adds reactivity, DaisyUI provides components, and TailwindCSS handles styling.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php require('partials/footer.php') ?>