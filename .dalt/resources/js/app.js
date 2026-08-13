import '../css/input.css'
import './code-copy.js'

document.addEventListener('DOMContentLoaded', async () => {
  const island = document.querySelector('[data-vue]')
  if (!island) return

  const { createApp } = await import('vue')
  const app = createApp({})
  if (island.querySelector('challenge-verifier')) app.component('challenge-verifier', (await import('./components/ChallengeVerifier.vue')).default)
  if (island.querySelector('unlock-button')) app.component('unlock-button', (await import('./components/UnlockButton.vue')).default)
  app.mount(island)
})
