import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const __dirname = dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  root: __dirname,
  base: '/',
  publicDir: false,
  server: {
    strictPort: true,
    port: 5173,
    host: true,
    origin: 'http://localhost:5173',
    cors: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources')
    }
  },
  plugins: [
    tailwindcss()
  ],
  build: {
    manifest: true,
    outDir: resolve(__dirname, 'public/build'),
    emptyOutDir: true,
    rollupOptions: {
      input: resolve(__dirname, 'resources/js/app.js')
    }
  }
})
