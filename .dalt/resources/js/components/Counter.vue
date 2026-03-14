<template>
  <div class="inline-flex items-center gap-3">
    <button 
      @click="decrement" 
      class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded transition"
    >
      -
    </button>
    <span class="font-mono text-lg">{{ count }}</span>
    <button 
      @click="increment" 
      class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded transition"
    >
      +
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const count = ref(0)

const increment = () => count.value++
const decrement = () => count.value--
</script>
