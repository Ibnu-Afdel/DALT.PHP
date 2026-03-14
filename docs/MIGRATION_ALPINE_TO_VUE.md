# Migrating from Alpine.js to Vue 3

If you have an existing DALT.PHP project using Alpine.js, here's how to migrate to Vue 3.

## Quick Migration Steps

### 1. Update Dependencies

```bash
npm uninstall alpinejs autoprefixer postcss esbuild
npm install vue@^3.5.13 @vitejs/plugin-vue@^5.2.1
npm install --save-dev tailwindcss@^4.0.0-beta.7 @tailwindcss/vite@^4.0.0-beta.7
```

### 2. Update Vite Config

Replace `vite.config.mjs`:

```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import FullReload from 'vite-plugin-full-reload'

export default defineConfig({
  // ... existing config
  plugins: [
    vue(),
    tailwindcss(),
    FullReload(['resources/views/**/*.php'])
  ],
  resolve: {
    alias: {
      '@': '/resources',
      'vue': 'vue/dist/vue.esm-bundler.js'
    }
  }
})
```

### 3. Update CSS

Replace `resources/css/input.css`:

```css
@import "tailwindcss";

/* Your custom CSS */
```

### 4. Update JavaScript Entry

Replace `resources/js/app.js`:

```javascript
import '../css/input.css'
import { createApp } from 'vue'

const components = import.meta.glob('./components/**/*.vue', { eager: true })
const app = createApp({})

Object.entries(components).forEach(([path, component]) => {
  const componentName = path.split('/').pop().replace(/\.\w+$/, '')
  app.component(componentName, component.default || component)
})

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-vue]').forEach(element => {
    app.mount(element)
  })
})
```

### 5. Delete Old Config Files

```bash
rm tailwind.config.js postcss.config.js
```

### 6. Convert Alpine Directives to Vue

**Alpine.js:**
```html
<div x-data="{ count: 0 }">
  <button @click="count++">Count: <span x-text="count"></span></button>
</div>
```

**Vue 3:**
```vue
<!-- resources/js/components/Counter.vue -->
<template>
  <div>
    <button @click="count++">Count: {{ count }}</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const count = ref(0)
</script>
```

```php
<!-- In your PHP view -->
<div data-vue>
  <Counter />
</div>
```

## Common Conversions

| Alpine.js | Vue 3 |
|-----------|-------|
| `x-data` | `<script setup>` with `ref()` |
| `x-text` | `{{ }}` interpolation |
| `x-show` | `v-show` |
| `x-if` | `v-if` |
| `@click` | `@click` (same!) |
| `x-model` | `v-model` |
| `x-for` | `v-for` |

## Benefits of Vue 3

- Better component organization
- TypeScript support
- More powerful reactivity
- Larger ecosystem
- Better IDE support

