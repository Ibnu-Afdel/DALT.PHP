<template>
  <div>
    <div v-if="error" class="mb-4 rounded-lg border border-red-500/50 bg-red-950/30 p-4 text-red-100" role="alert">
      <p class="font-medium">Lesson content could not be rendered</p>
      <p class="mt-1 text-sm text-red-200">{{ error }}</p>
    </div>
    <div v-else-if="!content" class="rounded-lg border border-amber-500/50 bg-amber-950/30 p-4 text-amber-100" role="status">
      <p>No lesson content is available.</p>
    </div>
    <div v-else  v-html="renderedContent"></div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { marked } from 'marked'

const content = ref('')
const error = ref('')

onMounted(() => {
  try {
    const dataElement = document.getElementById('lesson-content-data')
    if (dataElement) {
      content.value = JSON.parse(dataElement.textContent)
    } else {
      error.value = 'The lesson data is missing. Reload the page or return to the learning dashboard.'
    }
  } catch (e) {
    error.value = `The lesson data is invalid: ${e.message}`
  }
})

// Configure marked for better rendering
try {
  marked.setOptions({
    breaks: true,
    gfm: true,
  })
} catch (e) {
  error.value = `The Markdown renderer could not start: ${e.message}`
}

const renderedContent = computed(() => {
  try {
    if (!content.value) {
      return ''
    }
    return marked(content.value)
  } catch (e) {
    error.value = `The Markdown could not be parsed: ${e.message}`
    return ''
  }
})
</script>
