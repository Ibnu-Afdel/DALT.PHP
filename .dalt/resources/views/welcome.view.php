<?php require base_path('.dalt/resources/views/layouts/head.php') ?>
<?php require base_path('.dalt/resources/views/layouts/nav.php') ?>

<main class="flex-1 bg-[#0f1117] text-gray-300 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px]" id="app">
  <div class="max-w-6xl mx-auto px-6 py-12">
    <!-- Header -->
    <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <div class="flex flex-wrap items-center gap-2 mb-4">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Local Environment Active
          </div>
          <div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold">
            0.1.0-beta.2
          </div>
        </div>
        <h1 class="text-3xl font-bold text-gray-50 tracking-tight">Your Debugging Sandbox</h1>
        <p class="text-gray-400 mt-2">Fix deliberate backend bugs to master web architecture.</p>
      </div>
      <div class="flex items-center gap-3">
        <a href="https://github.com/Ibnu-Afdel/DALT.PHP/blob/main/TESTING_GUIDE.md" target="_blank" class="px-4 py-2 bg-[#1e293b] border border-gray-700 text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
          View Guide
        </a>
        <a href="/learn" class="px-4 py-2 bg-[#93DA97] text-[#0f1117] rounded-lg text-sm font-bold hover:bg-[#7bc87f] shadow-sm transition-colors">
          Open Course &rarr;
        </a>
      </div>
    </header>

    <div class="grid lg:grid-cols-3 gap-8">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- Challenge Quick Access -->
        <section>
          <h2 class="text-lg font-semibold text-gray-100 mb-4">Jump straight to a challenge</h2>
          <div class="grid sm:grid-cols-2 gap-4">
            
            <a href="/learn/challenges/broken-routing" class="group bg-[#161b22] rounded-xl border border-gray-800 p-5 hover:border-[#93DA97]/50 hover:bg-[#1a202c] transition-all flex items-start gap-4">
              <div class="w-10 h-10 bg-red-500/10 text-red-400 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-200 group-hover:text-[#93DA97] transition-colors">Broken Routing</h3>
                <p class="text-xs text-gray-500 mt-1">Fix route order and missing endpoints.</p>
              </div>
            </a>

            <a href="/learn/challenges/broken-middleware" class="group bg-[#161b22] rounded-xl border border-gray-800 p-5 hover:border-[#93DA97]/50 hover:bg-[#1a202c] transition-all flex items-start gap-4">
              <div class="w-10 h-10 bg-blue-500/10 text-blue-400 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-200 group-hover:text-[#93DA97] transition-colors">Broken Middleware</h3>
                <p class="text-xs text-gray-500 mt-1">Debug auth checks and filters.</p>
              </div>
            </a>

            <a href="/learn/challenges/broken-auth" class="group bg-[#161b22] rounded-xl border border-gray-800 p-5 hover:border-[#93DA97]/50 hover:bg-[#1a202c] transition-all flex items-start gap-4">
              <div class="w-10 h-10 bg-purple-500/10 text-purple-400 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-200 group-hover:text-[#93DA97] transition-colors">Broken Authentication</h3>
                <p class="text-xs text-gray-500 mt-1">Fix password validation and security.</p>
              </div>
            </a>

            <a href="/learn/challenges/broken-database" class="group bg-[#161b22] rounded-xl border border-gray-800 p-5 hover:border-[#93DA97]/50 hover:bg-[#1a202c] transition-all flex items-start gap-4">
              <div class="w-10 h-10 bg-emerald-500/10 text-emerald-400 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-200 group-hover:text-[#93DA97] transition-colors">Broken Database</h3>
                <p class="text-xs text-gray-500 mt-1">Patch SQL injection vulnerabilities.</p>
              </div>
            </a>

            <a href="/learn/challenges/broken-session" class="group bg-[#161b22] rounded-xl border border-gray-800 p-5 hover:border-[#93DA97]/50 hover:bg-[#1a202c] transition-all flex items-start gap-4">
              <div class="w-10 h-10 bg-amber-500/10 text-amber-400 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-200 group-hover:text-[#93DA97] transition-colors">Broken Session</h3>
                <p class="text-xs text-gray-500 mt-1">Fix state persistence and flash data.</p>
              </div>
            </a>
            
            <div class="bg-gray-800/20 rounded-xl border border-dashed border-gray-700/50 p-5 flex items-center justify-center text-gray-500 text-sm font-medium">
              More modules coming soon
            </div>

          </div>
        </section>
      </div>

      <!-- Right Sidebar: Terminal & Info -->
      <div class="space-y-6">
        
        <!-- Helpful Commands Block -->
        <div class="bg-[#0d1117] rounded-xl p-5 shadow-lg border border-gray-800">
          <div class="flex items-center gap-2 mb-4 text-gray-400 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="font-mono">Useful Commands</span>
          </div>
          
          <div class="space-y-4 font-mono text-xs">
            <div>
              <div class="text-gray-500 mb-1"># Test a specific challenge</div>
              <div class="bg-black/60 text-[#93DA97] px-3 py-2 rounded-md break-all border border-gray-800">
                php artisan challenge:verify
              </div>
            </div>
            <div>
              <div class="text-gray-500 mb-1"># Start your learning interface (you're here!)</div>
              <div class="bg-black/60 text-[#93DA97] px-3 py-2 rounded-md select-all border border-gray-800">
                php artisan serve
              </div>
            </div>
            <div>
              <div class="text-gray-500 mb-1"># Clear environment logs</div>
              <div class="bg-black/60 text-[#93DA97] px-3 py-2 rounded-md select-all border border-gray-800">
                rm storage/logs/challenges.log
              </div>
            </div>
          </div>
        </div>

        <!-- System Status -->
        <div class="bg-[#161b22] rounded-xl border border-gray-800 p-5">
           <h3 class="text-sm font-semibold text-gray-200 mb-3 uppercase tracking-wider">Workspace Details</h3>
           <ul class="space-y-2 text-sm text-gray-400">
             <li class="flex justify-between items-center">
               <span>PHP Version</span>
               <span class="font-mono bg-gray-900 border border-gray-700 px-2 py-1 rounded text-gray-300"><?= phpversion() ?></span>
             </li>
             <li class="flex justify-between items-center gap-2">
               <span>Directory</span>
               <span class="font-mono bg-gray-900 border border-gray-700 px-2 py-1 rounded text-gray-300 break-all text-xs text-right"><?= basename(base_path('')) ?></span>
             </li>
           </ul>
        </div>
        
      </div>
    </div>
  </div>
</main>
