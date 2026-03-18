import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import FullReload from 'vite-plugin-full-reload'
import { resolve } from 'path'

export default defineConfig({
  root: '.dalt',
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
      '.dalt/resources/views/**/*.php'
    ])
  ],
  build: {
    manifest: true,
    outDir: 'public/build',
    emptyOutDir: true,
    rollupOptions: {
      input: '.dalt/resources/js/app.js'
    }
  }
}) 