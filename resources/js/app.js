import '../css/input.css'
import { createApp } from 'vue'

// Auto-register all Vue components from the components directory
const components = import.meta.glob('./components/**/*.vue', { eager: true })

// Create Vue app instance
const app = createApp({})

// Register all components globally
Object.entries(components).forEach(([path, component]) => {
  const componentName = path
    .split('/')
    .pop()
    .replace(/\.\w+$/, '')
  
  app.component(componentName, component.default || component)
})

// Mount Vue to elements with [data-vue] attribute
document.addEventListener('DOMContentLoaded', () => {
  const vueElements = document.querySelectorAll('[data-vue]')
  
  vueElements.forEach(element => {
    app.mount(element)
  })
}) 