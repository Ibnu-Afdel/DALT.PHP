// `defineConfig` comes from vitest/config so the `test` block below is typed.
import { defineConfig } from 'vitest/config'
import react from '@vitejs/plugin-react'
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
    react(),
    tailwindcss()
  ],
  // Application tests. `passWithNoTests` keeps `npm test` green on a fresh
  // skeleton that has no application code yet.
  test: {
    environment: 'jsdom',
    globals: true,
    passWithNoTests: true,
    // Registers the jest-dom matchers. B03 Stage 4 has the learner port the Part 03
    // lab's tests here, and the lab registered them through its own vite config.
    setupFiles: [resolve(__dirname, 'resources/setup-tests.ts')],
    include: ['resources/**/*.{test,spec}.{ts,tsx,js,jsx}']
  },
  build: {
    manifest: true,
    outDir: resolve(__dirname, 'public/build'),
    emptyOutDir: true,
    rollupOptions: {
      input: resolve(__dirname, 'resources/js/app.js')
    }
  }
})
