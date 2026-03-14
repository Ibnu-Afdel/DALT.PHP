<template>
  <div class="bg-gray-800 rounded-xl p-6 max-w-md mx-auto border border-gray-700">
    <div class="space-y-4">
      <div class="flex items-center justify-center space-x-2 text-gray-400">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
        </svg>
        <span class="text-sm">{{ message }}</span>
      </div>
      <button 
        @click="tryUnlock" 
        class="w-full bg-red-900/20 text-red-400 hover:bg-red-900/40 px-4 py-2 rounded-lg text-sm transition-all duration-200"
      >
        Try to unlock?
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const message = ref('This area is locked 🔒')

const tryUnlock = () => {
  message.value = 'Still locked! You need proper credentials 🔑'
  setTimeout(() => {
    message.value = 'Maybe try logging in first? 😅'
  }, 2000)
}
</script>
