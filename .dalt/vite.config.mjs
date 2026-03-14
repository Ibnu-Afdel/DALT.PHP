import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import FullReload from 'vite-plugin-full-reload'
import { resolve } from 'path'

export default defineConfig({
  root: resolve(__dirname, '..'),
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
      '@': resolve(__dirname, '../resources'),
      'vue': 'vue/dist/vue.esm-bundler.js'
    }
  },
  plugins: [
    vue(),
    tailwindcss(),
    FullReload([
      resolve(__dirname, '../resources/views/**/*.php'),
      resolve(__dirname, 'resources/views/**/*.php')
    ])
  ],
  optimizeDeps: {
    entries: [
      resolve(__dirname, '../resources/js/app.js'),
      resolve(__dirname, 'resources/js/app.js')
    ]
  },
  build: {
    manifest: true,
    outDir: resolve(__dirname, '../public/build'),
    emptyOutDir: true,
    rollupOptions: {
      input: [
        resolve(__dirname, '../resources/js/app.js'),
        resolve(__dirname, 'resources/js/app.js')
      ]
    }
  }
}) 