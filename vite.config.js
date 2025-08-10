import { defineConfig } from 'vite'
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
      '@': '/resources'
    }
  },
  plugins: [
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