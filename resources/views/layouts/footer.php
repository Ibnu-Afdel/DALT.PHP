</div>
<!-- Footer -->
<footer class="bg-gray-900 border-t border-gray-700">
    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Brand Section -->
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <svg class="w-8 h-8 text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7v10c0 5.55 3.84 9.95 9 11 5.16-1.05 9-5.45 9-11V7l-10-5z"/>
                        <path d="M12 4.5L4.5 8.5v8.5c0 4.5 3.5 8 7.5 8s7.5-3.5 7.5-8V8.5L12 4.5z" fill="white"/>
                    </svg>
                    <h3 class="text-xl font-bold text-white">DALTPHP</h3>
                </div>
                <p class="text-gray-400 mb-4 max-w-md">Minimal educational PHP micro-framework.</p>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="/" class="text-gray-400 hover:text-indigo-400 transition-colors">Home</a></li>
                </ul>
            </div>
            
            <!-- Resources -->
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Resources</h4>
                <ul class="space-y-2">
                    <li><a href="https://tailwindcss.com" target="_blank" class="text-gray-400 hover:text-indigo-400 transition-colors">TailwindCSS</a></li>
                    <li><a href="https://daisyui.com" target="_blank" class="text-gray-400 hover:text-indigo-400 transition-colors">DaisyUI</a></li>
                    <li><a href="https://alpinejs.dev" target="_blank" class="text-gray-400 hover:text-indigo-400 transition-colors">Alpine.js</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Bottom Section -->
        <div class="border-t border-gray-700 mt-8 pt-8 flex justify-between items-center">
            <p class="text-gray-400 text-sm">
                © <?= date('Y') ?> DALTPHP. All rights reserved.
            </p>
        </div>
    </div>
</footer>
</body>
</html>