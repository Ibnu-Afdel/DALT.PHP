import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import FullReload from 'vite-plugin-full-reload'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const __dirname = dirname(fileURLToPath(import.meta.url))

export default defineConfig({
  root: __dirname,
  base: '/dalt-assets/',
  publicDir: false,
  server: { strictPort: true, port: 5174, host: true, origin: 'http://localhost:5174' },
  resolve: { alias: { '@': resolve(__dirname, 'resources'), vue: 'vue/dist/vue.esm-bundler.js' } },
  plugins: [vue(), tailwindcss(), FullReload([resolve(__dirname, 'resources/views/**/*.php')])],
  build: {
    manifest: true,
    outDir: resolve(__dirname, 'build'),
    emptyOutDir: true,
    rollupOptions: { input: resolve(__dirname, 'resources/js/app.js') }
  }
})
