<template>
  <div class="rounded-xl border bg-white p-6">
    <h3 class="font-semibold">{{ title }}</h3>
    <p class="mt-3 text-sm">{{ message }}</p>
    <button 
      @click="count++" 
      class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition"
    >
      Clicked {{ count }} times
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  title: {
    type: String,
    default: 'Vue 3 Component'
  },
  message: {
    type: String,
    default: 'This is an example Vue 3 component with Tailwind CSS v4'
  }
})

const count = ref(0)
</script>
