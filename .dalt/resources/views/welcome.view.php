<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/nav.php') ?>

<main class="flex-1" id="app">
  <!-- Hero Section -->
  <section class="relative overflow-hidden bg-gradient-to-br from-[#3E5F44] via-[#2d4532] to-[#1a2a1f] text-white">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-40"></div>
    
    <div class="relative max-w-7xl mx-auto px-6 py-24 sm:py-32">
      <div class="text-center max-w-4xl mx-auto">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 mb-8">
          <span class="w-2 h-2 bg-[#93DA97] rounded-full animate-pulse"></span>
          <span class="text-sm font-medium">Interactive Backend Debugging Playground</span>
        </div>
        
        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight mb-6">
          Learn Backend by
          <span class="block text-[#93DA97] mt-2">Fixing Real Bugs</span>
        </h1>
        
        <p class="text-xl sm:text-2xl text-gray-300 mb-12 max-w-3xl mx-auto leading-relaxed">
          DALT.PHP is an educational platform where you debug intentionally broken backend code to master web framework concepts.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <a href="/learn" class="px-8 py-4 bg-[#93DA97] text-[#1a2a1f] rounded-lg font-semibold hover:bg-[#7bc87f] transition-all transform hover:scale-105 shadow-lg">
            Start Learning
          </a>
          <a href="#how-it-works" class="px-8 py-4 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg font-semibold hover:bg-white/20 transition-all">
            How It Works
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="py-12 bg-white border-b">
    <div class="max-w-7xl mx-auto px-6">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
        <div>
          <div class="text-4xl font-bold text-[#3E5F44]">5</div>
          <div class="text-sm text-gray-600 mt-1">Challenges</div>
        </div>
        <div>
          <div class="text-4xl font-bold text-[#3E5F44]">9</div>
          <div class="text-sm text-gray-600 mt-1">Real Bugs</div>
        </div>
        <div>
          <div class="text-4xl font-bold text-[#3E5F44]">19</div>
          <div class="text-sm text-gray-600 mt-1">Auto Tests</div>
        </div>
        <div>
          <div class="text-4xl font-bold text-[#3E5F44]">5</div>
          <div class="text-sm text-gray-600 mt-1">Lessons</div>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section id="how-it-works" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-gray-900 mb-4">How It Works</h2>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          Learn by doing. Debug real backend issues in a safe, local environment.
        </p>
      </div>

      <div class="grid md:grid-cols-3 gap-8">
        <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
          <div class="w-12 h-12 bg-[#3E5F44] rounded-lg flex items-center justify-center text-white font-bold text-xl mb-4">1</div>
          <h3 class="text-xl font-semibold mb-3">Read the Lesson</h3>
          <p class="text-gray-600">
            Start with comprehensive lessons covering routing, middleware, authentication, database, and sessions.
          </p>
        </div>

        <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
          <div class="w-12 h-12 bg-[#3E5F44] rounded-lg flex items-center justify-center text-white font-bold text-xl mb-4">2</div>
          <h3 class="text-xl font-semibold mb-3">Debug the Code</h3>
          <p class="text-gray-600">
            Inspect intentionally broken backend code. Find bugs like SQL injection, auth bypass, and routing errors.
          </p>
        </div>

        <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
          <div class="w-12 h-12 bg-[#3E5F44] rounded-lg flex items-center justify-center text-white font-bold text-xl mb-4">3</div>
          <h3 class="text-xl font-semibold mb-3">Verify Your Fix</h3>
          <p class="text-gray-600">
            Run automated tests to verify your solution. Get instant feedback with hints if something's still broken.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Challenges Section -->
  <section id="challenges" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-gray-900 mb-4">Available Challenges</h2>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          Five hands-on challenges covering essential backend concepts
        </p>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Challenge 1 -->
        <a href="/learn/challenges/broken-routing" class="bg-white rounded-xl border-2 border-gray-200 p-6 hover:border-[#93DA97] transition-all hover:shadow-lg block">
          <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
              </svg>
            </div>
            <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Routing</span>
          </div>
          <h3 class="text-xl font-semibold mb-2">Broken Routing</h3>
          <p class="text-gray-600 text-sm mb-4">Fix route order issues and missing route registrations. Learn how routers match URLs.</p>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500">2 bugs to fix</span>
            <span class="text-[#3E5F44] font-medium">→</span>
          </div>
        </a>

        <!-- Challenge 2 -->
        <a href="/learn/challenges/broken-middleware" class="bg-white rounded-xl border-2 border-gray-200 p-6 hover:border-[#93DA97] transition-all hover:shadow-lg block">
          <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
              </svg>
            </div>
            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Middleware</span>
          </div>
          <h3 class="text-xl font-semibold mb-2">Broken Middleware</h3>
          <p class="text-gray-600 text-sm mb-4">Debug auth checks and CSRF validation. Understand middleware execution flow.</p>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500">2 bugs to fix</span>
            <span class="text-[#3E5F44] font-medium">→</span>
          </div>
        </a>

        <!-- Challenge 3 -->
        <a href="/learn/challenges/broken-auth" class="bg-white rounded-xl border-2 border-gray-200 p-6 hover:border-[#93DA97] transition-all hover:shadow-lg block">
          <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full">Auth</span>
          </div>
          <h3 class="text-xl font-semibold mb-2">Broken Authentication</h3>
          <p class="text-gray-600 text-sm mb-4">Fix insecure password comparison. Learn proper authentication patterns.</p>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500">1 bug to fix</span>
            <span class="text-[#3E5F44] font-medium">→</span>
          </div>
        </a>

        <!-- Challenge 4 -->
        <a href="/learn/challenges/broken-database" class="bg-white rounded-xl border-2 border-gray-200 p-6 hover:border-[#93DA97] transition-all hover:shadow-lg block">
          <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
              </svg>
            </div>
            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Database</span>
          </div>
          <h3 class="text-xl font-semibold mb-2">Broken Database</h3>
          <p class="text-gray-600 text-sm mb-4">Patch SQL injection vulnerabilities. Master prepared statements and safe queries.</p>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500">2 bugs to fix</span>
            <span class="text-[#3E5F44] font-medium">→</span>
          </div>
        </a>

        <!-- Challenge 5 -->
        <a href="/learn/challenges/broken-session" class="bg-white rounded-xl border-2 border-gray-200 p-6 hover:border-[#93DA97] transition-all hover:shadow-lg block">
          <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-full">Session</span>
          </div>
          <h3 class="text-xl font-semibold mb-2">Broken Session</h3>
          <p class="text-gray-600 text-sm mb-4">Fix flash data handling and session persistence. Understand session lifecycle.</p>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500">2 bugs to fix</span>
            <span class="text-[#3E5F44] font-medium">→</span>
          </div>
        </a>

        <!-- Coming Soon -->
        <div class="bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 p-6 flex items-center justify-center">
          <div class="text-center">
            <div class="text-gray-400 mb-2">
              <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
            </div>
            <p class="text-gray-500 font-medium">More challenges coming soon</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Getting Started Section -->
  <section class="py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-4xl font-bold text-gray-900 mb-4">Get Started in Minutes</h2>
        <p class="text-xl text-gray-600">Clone, install, and start debugging</p>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-200">
        <div class="space-y-4">
          <div class="bg-gray-900 rounded-lg p-6 font-mono text-sm text-gray-300 overflow-x-auto">
            <div class="text-gray-500"># Clone the repository</div>
            <div class="text-[#93DA97]">git clone https://github.com/Ibnu-Afdel/DALT.PHP.git</div>
            <div class="mt-4 text-gray-500"># Install dependencies</div>
            <div class="text-[#93DA97]">composer install && npm install</div>
            <div class="mt-4 text-gray-500"># Setup environment</div>
            <div class="text-[#93DA97]">cp .env.example .env</div>
            <div class="text-[#93DA97]">php artisan migrate</div>
            <div class="mt-4 text-gray-500"># Start servers</div>
            <div class="text-[#93DA97]">npm run dev</div>
            <div class="text-[#93DA97]">php artisan serve</div>
            <div class="mt-4 text-gray-500"># Verify a challenge</div>
            <div class="text-[#93DA97]">php artisan verify broken-routing</div>
          </div>
        </div>

        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm">
              <p class="font-semibold text-blue-900 mb-1">Need help?</p>
              <p class="text-blue-800">Check out the <span class="font-mono bg-blue-100 px-1 rounded">TESTING_GUIDE.md</span> for detailed instructions and troubleshooting.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Tech Stack Section -->
  <section class="py-20 bg-white border-t">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Built With Modern Tools</h2>
        <p class="text-lg text-gray-600">A clean, minimal stack focused on learning</p>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="text-center p-6">
          <div class="text-4xl mb-3">🐘</div>
          <h3 class="font-semibold text-lg mb-2">PHP 8+</h3>
          <p class="text-sm text-gray-600">Modern PHP with type safety and clean syntax</p>
        </div>
        <div class="text-center p-6">
          <div class="text-4xl mb-3">💚</div>
          <h3 class="font-semibold text-lg mb-2">Vue 3</h3>
          <p class="text-sm text-gray-600">Progressive framework for interactive UIs</p>
        </div>
        <div class="text-center p-6">
          <div class="text-4xl mb-3">🎨</div>
          <h3 class="font-semibold text-lg mb-2">Tailwind v4</h3>
          <p class="text-sm text-gray-600">Latest utility-first CSS framework</p>
        </div>
        <div class="text-center p-6">
          <div class="text-4xl mb-3">⚡</div>
          <h3 class="font-semibold text-lg mb-2">Vite</h3>
          <p class="text-sm text-gray-600">Lightning-fast build tool and dev server</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-20 bg-gradient-to-br from-[#3E5F44] to-[#2d4532] text-white">
    <div class="max-w-4xl mx-auto px-6 text-center">
      <h2 class="text-4xl font-bold mb-6">Ready to Master Backend Development?</h2>
      <p class="text-xl text-gray-300 mb-8">
        Start debugging real backend issues and build production-ready skills
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="https://github.com/Ibnu-Afdel/DALT.PHP" target="_blank" class="px-8 py-4 bg-white text-[#3E5F44] rounded-lg font-semibold hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
          View on GitHub
        </a>
        <a href="https://t.me/daltphp" target="_blank" class="px-8 py-4 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg font-semibold hover:bg-white/20 transition-all">
          Join Community
        </a>
      </div>
    </div>
  </section>
</main>

<?php require base_path('.dalt/resources/views/layouts/footer.php') ?>
