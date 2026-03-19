import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import FullReload from 'vite-plugin-full-reload'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const __dirname = dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  root: __dirname,
  base: '/',
  publicDir: false,
  appType: 'custom',
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
      '@': resolve(__dirname, '.dalt/resources'),
      'vue': 'vue/dist/vue.esm-bundler.js'
    }
  },
  plugins: [
    vue(),
    tailwindcss(),
    FullReload([
      resolve(__dirname, '.dalt/resources/views/**/*.php')
    ])
  ],
  build: {
    manifest: true,
    outDir: resolve(__dirname, 'public/build'),
    emptyOutDir: true,
    rollupOptions: {
      input: resolve(__dirname, '.dalt/resources/js/app.js')
    }
  }
}) 