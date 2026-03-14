import '../css/input.css'
import { createApp } from 'vue'

// Auto-register all Vue components from the components directory
const components = import.meta.glob('./components/**/*.vue', { eager: true })

// Create Vue app instance
const app = createApp({})

// Register all components globally with kebab-case names
Object.entries(components).forEach(([path, component]) => {
  const componentName = path
    .split('/')
    .pop()
    .replace(/\.\w+$/, '')
  
  // Convert PascalCase to kebab-case for HTML usage
  const kebabName = componentName
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .toLowerCase()
  
  app.component(kebabName, component.default || component)
})

// Mount Vue to #app or elements with [data-vue] attribute
document.addEventListener('DOMContentLoaded', () => {
  // Mount to #app if it exists
  const appElement = document.getElementById('app')
  if (appElement) {
    app.mount('#app')
  } else {
    // Fallback to data-vue elements
    const vueElements = document.querySelectorAll('[data-vue]')
    vueElements.forEach(element => {
      createApp({}).mount(element)
    })
  }
}) 