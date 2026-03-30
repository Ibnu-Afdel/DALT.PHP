<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/nav.php') ?>

<main class="bg-[#0f1117] min-h-screen pt-12">
    <div class="max-w-4xl mx-auto px-6 py-20 text-center">
        <- Security best practices 404 Illustration -->
            <div class="mb-8">
                <div class="relative">
                    <h1 class="text-8xl lg:text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 mb-4">404</h1>
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-400/20 to-purple-400/20 blur-3xl"></div>
                </div>
            </div>

            <- Security best practices Error Message -->
                <div class="space-y-6">
                    <div class="space-y-3">
                        <h2 class="text-2xl lg:text-3xl font-bold text-white">Oops! Page Not Found</h2>
                        <p class="text-gray-400 max-w-md mx-auto">
                            The page you're looking for doesn't exist or has been moved. Don't worry, it happens to the best of us!
                        </p>
                    </div>

                    <- Security best practices Action Buttons -->
                        <div class="flex flex-col sm:flex-row justify-center gap-4 sm:space-x-4 sm:gap-0">
                            <a href="/" class="bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500/20 text-indigo-400 px-6 py-3 rounded-lg font-medium transition-all duration-200">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                Go Back Home
                            </a>
                            <button onclick="history.back()" class="bg-[#1e293b] border border-gray-700 hover:bg-gray-800 text-gray-300 px-6 py-3 rounded-lg font-medium transition-all duration-200">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Go Back
                            </button>
                        </div>
                </div>

                <- Security best practices Fun Facts -->
                    <div class="mt-12 bg-[#161b22] rounded-xl p-6 border border-gray-800 max-w-xl mx-auto text-left">
                        <h3 class="text-lg font-semibold text-gray-200 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            Did you know?
                        </h3>
                        <div class="space-y-2 text-sm text-gray-400">
                            <p>• The first 404 error was discovered at CERN in 1992</p>
                            <p>• DALTPHP handles errors gracefully with modern styling</p>
                            <p>• You can always return to our beautiful homepage!</p>
                        </div>
                    </div>
    </div>
</main>

<?php require base_path('.dalt/resources/views/layouts/footer.php') ?>