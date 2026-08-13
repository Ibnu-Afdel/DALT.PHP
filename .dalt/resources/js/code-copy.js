document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.learn-prose pre').forEach((pre) => {
    const code = pre.querySelector('code')
    if (!code || !navigator.clipboard) return
    const language = [...code.classList].find((name) => name.startsWith('language-'))?.slice(9)
    if (language) pre.dataset.language = language
    const button = document.createElement('button')
    button.type = 'button'
    button.className = 'code-copy'
    button.textContent = 'Copy'
    button.setAttribute('aria-label', 'Copy code to clipboard')
    button.addEventListener('click', async () => {
      try { await navigator.clipboard.writeText(code.textContent || '') } catch { return }
      button.textContent = 'Copied'
      window.setTimeout(() => { button.textContent = 'Copy' }, 1400)
    })
    pre.append(button)
  })
})
