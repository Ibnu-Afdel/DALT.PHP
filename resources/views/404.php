<?php require('partials/head.php') ?>
<?php require('partials/nav.php') ?>

<main class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
    <div class="max-w-4xl mx-auto px-6 py-20 text-center">
        <!-- 404 Illustration -->
        <div class="mb-8">
            <div class="relative">
                <h1 class="text-8xl lg:text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 mb-4">404</h1>
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-400/20 to-purple-400/20 blur-3xl"></div>
            </div>
        </div>

        <!-- Error Message -->
        <div class="space-y-6">
            <div class="space-y-3">
                <h2 class="text-2xl lg:text-3xl font-bold text-white">Oops! Page Not Found</h2>
                <p class="text-gray-300 max-w-md mx-auto">
                    The page you're looking for doesn't exist or has been moved. 
                    Don't worry, it happens to the best of us!
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-center gap-4 sm:space-x-4 sm:gap-0">
                <a href="/" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go Back Home
                </a>
                <button onclick="history.back()" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-6 py-3 rounded-lg font-medium transition-all duration-200">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Go Back
                </button>
            </div>
        </div>

        <!-- Fun Facts -->
        <div class="mt-12 bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">💡 Did you know?</h3>
            <div class="space-y-2 text-sm text-gray-300">
                <p>• The first 404 error was discovered at CERN in 1992</p>
                <p>• DALTPHP handles errors gracefully with modern styling</p>
                <p>• You can always return to our beautiful homepage!</p>
            </div>
        </div>
    </div>
</main>

<?php require('partials/footer.php') ?>
