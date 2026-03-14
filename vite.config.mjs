import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import FullReload from 'vite-plugin-full-reload'

export default defineConfig({
  root: '.',
  base: '/',
  publicDir: false,
  appType: 'custom',
  server: {
    strictPort: true,
    port: 5173,
    host: true,
    origin: 'http://localhost:5173',
    cors: true
  },
  resolve: {
    alias: {
      '@': '/resources',
      'vue': 'vue/dist/vue.esm-bundler.js'
    }
  },
  plugins: [
    vue(),
    tailwindcss(),
    FullReload(['resources/views/**/*.php'])
  ],
  optimizeDeps: {
    entries: ['resources/js/app.js']
  },
  build: {
    manifest: true,
    outDir: 'public/build',
    emptyOutDir: true,
    rollupOptions: {
      input: '/resources/js/app.js'
    }
  }
}) 