import { defineConfig } from 'vite'
import FullReload from 'vite-plugin-full-reload'

export default defineConfig({
  root: '.',
  base: '/',
  publicDir: false,
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
  build: {
    manifest: true,
    outDir: 'public/build',
    emptyOutDir: true,
    rollupOptions: {
      input: '/resources/js/app.js'
    }
  }
}) 