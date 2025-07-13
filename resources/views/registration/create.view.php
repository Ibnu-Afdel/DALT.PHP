<?php require base_path('resources/views/partials/head.php') ?>
<?php require base_path('resources/views/partials/nav.php') ?>

<main class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-sm">
                <!-- Modern Shield Logo -->
                <div class="mx-auto h-12 w-12 flex items-center justify-center">
                    <svg viewBox="0 0 100 100" class="h-12 w-12">
                        <defs>
                            <linearGradient id="shieldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#818cf8;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#4f46e5;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <path d="M50 10 L80 25 L80 45 Q80 70 50 90 Q20 70 20 45 L20 25 Z" fill="url(#shieldGradient)" stroke="#e5e7eb" stroke-width="2"/>
                        <path d="M35 45 L45 55 L65 35" stroke="white" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-white">Create Account</h2>
                <p class="mt-2 text-center text-sm text-gray-300">Join the DALTPHP community</p>
            </div>

            <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-700/50 p-8">
                    <form class="space-y-6" action="/register" method="POST">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-200">Full Name</label>
                            <div class="mt-2">
                                <input type="text" name="name" id="name" required value="<?= old('name') ?>"
                                       class="block w-full rounded-lg bg-gray-700/50 backdrop-blur-sm border border-gray-600 px-4 py-3 text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition-colors duration-200 sm:text-sm"/>
                            </div>
                            <?php if(isset($errors['name'])) :?>
                                <p class="text-red-400 font-medium text-xs mt-2 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <?= $errors['name'] ?>
                                </p>
                            <?php endif ?>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-200">Email address</label>
                            <div class="mt-2">
                                <input type="email" name="email" id="email" required value="<?= old('email') ?>"
                                       class="block w-full rounded-lg bg-gray-700/50 backdrop-blur-sm border border-gray-600 px-4 py-3 text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition-colors duration-200 sm:text-sm"/>
                            </div>
                            <?php if(isset($errors['email'])) :?>
                                <p class="text-red-400 font-medium text-xs mt-2 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <?= $errors['email'] ?>
                                </p>
                            <?php endif ?>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-200">Password</label>
                            <div class="mt-2">
                                <input type="password" name="password" id="password" autocomplete="new-password" required
                                       class="block w-full rounded-lg bg-gray-700/50 backdrop-blur-sm border border-gray-600 px-4 py-3 text-white placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition-colors duration-200 sm:text-sm"/>
                            </div>
                            <?php if(isset($errors['password'])) :?>
                                <p class="text-red-400 font-medium text-xs mt-2 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <?= $errors['password'] ?>
                                </p>
                            <?php endif ?>
                        </div>

                        <div>
                            <button type="submit"
                                    class="flex w-full justify-center rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-800 transform transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                                Create Account
                            </button>
                        </div>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-400">
                            Already have an account?
                            <a href="/login" class="font-semibold text-indigo-400 hover:text-indigo-300 transition-colors duration-200">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require base_path('resources/views/partials/footer.php') ?>

