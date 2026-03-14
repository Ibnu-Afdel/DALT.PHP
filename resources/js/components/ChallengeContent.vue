<template>
  <div>
    <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
      <p class="text-red-800 font-medium">Error rendering content:</p>
      <p class="text-red-600 text-sm">{{ error }}</p>
    </div>
    <div v-else-if="!content" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <p class="text-yellow-800">No content provided</p>
    </div>
    <div v-else class="prose prose-lg max-w-none" v-html="renderedContent"></div>
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

<style scoped>
.prose {
  color: #374151;
}

.prose :deep(h1) {
  font-size: 2rem;
  font-weight: 700;
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: #111827;
}

.prose :deep(h2) {
  font-size: 1.5rem;
  font-weight: 700;
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: #111827;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.prose :deep(h3) {
  font-size: 1.25rem;
  font-weight: 600;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  color: #1f2937;
}

.prose :deep(p) {
  margin-bottom: 1rem;
  line-height: 1.75;
}

.prose :deep(ul), .prose :deep(ol) {
  margin-bottom: 1rem;
  padding-left: 1.5rem;
}

.prose :deep(li) {
  margin-bottom: 0.5rem;
}

.prose :deep(code) {
  background-color: #fef3c7;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  font-size: 0.875em;
  font-family: 'Courier New', monospace;
  color: #92400e;
}

.prose :deep(pre) {
  background-color: #1f2937;
  color: #e5e7eb;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin-bottom: 1rem;
}

.prose :deep(pre code) {
  background-color: transparent;
  padding: 0;
  color: inherit;
  font-size: 0.875rem;
}

.prose :deep(blockquote) {
  border-left: 4px solid #f59e0b;
  background-color: #fffbeb;
  padding: 1rem;
  padding-left: 1.5rem;
  margin: 1.5rem 0;
  border-radius: 0.25rem;
}

.prose :deep(blockquote p) {
  margin: 0;
  color: #92400e;
}

.prose :deep(a) {
  color: #3E5F44;
  text-decoration: underline;
}

.prose :deep(a:hover) {
  color: #2d4532;
}

.prose :deep(strong) {
  font-weight: 600;
  color: #111827;
}
</style>
