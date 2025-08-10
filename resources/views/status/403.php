<?php require('partials/head.php') ?>
<?php require('partials/nav.php') ?>

<main class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
    <div class="max-w-4xl mx-auto px-6 py-20 text-center">
        <!-- 403 Illustration -->
        <div class="mb-8">
            <div class="relative">
                <h1 class="text-8xl lg:text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-orange-400 mb-4">403</h1>
                <div class="absolute inset-0 bg-gradient-to-r from-red-400/20 to-orange-400/20 blur-3xl"></div>
            </div>
        </div>

        <!-- Error Message -->
        <div class="space-y-6">
            <div class="space-y-3">
                <h2 class="text-2xl lg:text-3xl font-bold text-white">Access Denied</h2>
                <p class="text-gray-300 max-w-md mx-auto">
                    You don't have permission to access this resource. 
                    This area is restricted and requires proper authorization.
                </p>
            </div>

            <!-- Interactive Element -->
            <div class="bg-gray-800 rounded-xl p-6 max-w-md mx-auto border border-gray-700">
                <div x-data="{ 
                    locked: true, 
                    message: 'This area is locked 🔒',
                    tryUnlock() {
                        this.message = 'Still locked! You need proper credentials 🔑';
                        setTimeout(() => {
                            this.message = 'Maybe try logging in first? 😅';
                        }, 2000);
                    }
                }" class="space-y-4">
                    <div class="flex items-center justify-center space-x-2 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <span class="text-sm" x-text="message"></span>
                    </div>
                    <button @click="tryUnlock()" 
                            class="w-full bg-red-900/20 text-red-400 hover:bg-red-900/40 px-4 py-2 rounded-lg text-sm transition-all duration-200">
                        Try to unlock?
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-center gap-4 sm:space-x-4 sm:gap-0">
                <a href="/login" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Sign In
                </a>
                <a href="/" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-6 py-3 rounded-lg font-medium transition-all duration-200">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go Home
                </a>
            </div>
        </div>

        <!-- Security Info -->
        <div class="mt-12 bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">🔒 Security Notice</h3>
            <div class="space-y-2 text-sm text-gray-300">
                <p>• This resource requires proper authentication</p>
                <p>• DALTPHP protects your data with robust security</p>
                <p>• Contact an administrator if you believe this is an error</p>
            </div>
        </div>
    </div>
</main>

<?php require('partials/footer.php') ?>
