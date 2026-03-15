<template>
  <div>
    <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
      <p class="text-red-800 font-medium">Error rendering content:</p>
      <p class="text-red-600 text-sm">{{ error }}</p>
    </div>
    <div v-else-if="!content" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <p class="text-yellow-800">No content provided</p>
    </div>
    <div v-else  v-html="renderedContent"></div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { marked } from 'marked';

const content = ref('');
const error = ref(null);

onMounted(() => {
  try {
    const dataElement = document.getElementById('challenge-content-data');
    if (dataElement) {
      content.value = JSON.parse(dataElement.textContent);
    } else {
      error.value = 'Content data not found';
    }
  } catch (e) {
    error.value = `Failed to load content: ${e.message}`;
  }
});

try {
  marked.setOptions({
    breaks: true,
    gfm: true,
    headerIds: true
  });
} catch (e) {
  error.value = `Marked configuration error: ${e.message}`;
}

const renderedContent = computed(() => {
  try {
    if (!content.value) {
      return '<p class="text-gray-500">Loading content...</p>';
    }
    return marked(content.value);
  } catch (e) {
    error.value = `Markdown parsing error: ${e.message}`;
    return '';
  }
});
</script>

