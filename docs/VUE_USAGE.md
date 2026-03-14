# Using Vue 3 in DALT.PHP

DALT.PHP comes with Vue 3 pre-configured for adding interactive components to your PHP views.

## Quick Start

### 1. Create a Vue Component

Create a `.vue` file in `resources/js/components/`:

```vue
<!-- resources/js/components/HelloWorld.vue -->
<template>
  <div class="p-4 bg-blue-100 rounded">
    <h2 class="text-xl font-bold">{{ message }}</h2>
    <button @click="count++" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded">
      Clicked {{ count }} times
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const message = ref('Hello from Vue 3!')
const count = ref(0)
</script>
```

### 2. Use in PHP Views

Add a `data-vue` attribute to mount Vue:

```php
<!-- resources/views/welcome.view.php -->
<div data-vue>
  <HelloWorld />
</div>
```

### 3. Run the Dev Server

```bash
npm run dev
```

That's it! Vue components are auto-registered and ready to use.

## How It Works

- All `.vue` files in `resources/js/components/` are automatically registered
- Components are globally available (no imports needed in views)
- Vue mounts on any element with `data-vue` attribute
- Tailwind CSS v4 classes work seamlessly in Vue templates

## Examples

Check `resources/js/components/` for example components:
- `Counter.vue` - Simple counter with increment/decrement
- `ExampleComponent.vue` - Component with props

