<template>
  <section aria-labelledby="verification-title">
    <div class="mb-6">
      <h2 id="verification-title" class="text-2xl font-bold text-gray-100">Verify your solution</h2>
      <p class="mt-2 max-w-2xl text-gray-400">
        Run the structural checks for the challenge currently loaded in your project.
      </p>
    </div>

    <button
      type="button"
      :disabled="isVerifying"
      class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#3E5F44] px-5 py-3 font-medium text-white transition-colors hover:bg-[#4b7253] disabled:cursor-not-allowed disabled:opacity-60"
      @click="runVerification"
    >
      <svg v-if="isVerifying" aria-hidden="true" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
        <path class="opacity-90" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
      </svg>
      <svg v-else aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
      </svg>
      {{ isVerifying ? 'Running verification…' : 'Run verification' }}
    </button>

    <div class="sr-only" role="status" aria-live="polite" aria-atomic="true">
      {{ announcement }}
    </div>

    <div v-if="result" class="mt-6">
      <div v-if="result.status === 'pass'" class="rounded-xl border border-emerald-500/50 bg-emerald-950/30 p-6 text-emerald-100">
        <div class="flex items-start gap-3">
          <svg aria-hidden="true" class="mt-0.5 h-7 w-7 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="min-w-0">
            <h3 class="text-xl font-bold">All checks passed</h3>
            <p class="mt-2 overflow-wrap-anywhere text-emerald-200">{{ result.message }}</p>
            <p v-if="result.timestamp" class="mt-3 text-sm text-emerald-300">Verified {{ formatTimestamp(result.timestamp) }}</p>
          </div>
        </div>
        <ul v-if="result.tests.length" class="mt-5 space-y-2 border-t border-emerald-500/30 pt-4">
          <li v-for="test in result.tests" :key="test.name" class="flex items-start gap-2 text-sm text-emerald-200">
            <svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="min-w-0 overflow-wrap-anywhere">{{ test.message }}</span>
          </li>
        </ul>
        <a href="/learn" class="mt-6 inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Choose another challenge</a>
      </div>

      <div v-else-if="result.status === 'not_loaded'" class="rounded-xl border border-amber-500/50 bg-amber-950/30 p-6 text-amber-100">
        <div class="flex items-start gap-3">
          <svg aria-hidden="true" class="mt-0.5 h-7 w-7 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.3 4.3L2.5 18a2 2 0 001.7 3h15.6a2 2 0 001.7-3L13.7 4.3a2 2 0 00-3.4 0z" />
          </svg>
          <div class="min-w-0">
            <h3 class="text-xl font-bold">Challenge not loaded</h3>
            <p class="mt-2 overflow-wrap-anywhere text-amber-200">{{ result.message }}</p>
          </div>
        </div>
      </div>

      <div v-else class="rounded-xl border border-red-500/50 bg-red-950/30 p-6 text-red-100">
        <div class="flex items-start gap-3">
          <svg aria-hidden="true" class="mt-0.5 h-7 w-7 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="min-w-0">
            <h3 class="text-xl font-bold">Checks still failing</h3>
            <p class="mt-2 overflow-wrap-anywhere text-red-200">{{ result.message }}</p>
            <p v-if="result.timestamp" class="mt-3 text-sm text-red-300">Tested {{ formatTimestamp(result.timestamp) }}</p>
          </div>
        </div>

        <ul v-if="result.tests.length" class="mt-5 space-y-3 border-t border-red-500/30 pt-4" aria-label="Verification checks">
          <li
            v-for="test in result.tests"
            :key="test.name"
            class="rounded-lg border p-4"
            :class="test.passed ? 'border-emerald-500/30 bg-emerald-950/30 text-emerald-100' : 'border-red-500/30 bg-red-950/40 text-red-100'"
          >
            <div class="flex items-start gap-2">
              <svg v-if="test.passed" aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <div class="min-w-0">
                <p class="overflow-wrap-anywhere text-sm font-medium">{{ test.message }}</p>
                <p v-if="test.hint && !test.passed" class="mt-2 overflow-wrap-anywhere text-sm text-red-200">
                  <span class="font-semibold">Next hint:</span> {{ test.hint }}
                </p>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <div v-if="error" class="mt-6 rounded-xl border border-amber-500/50 bg-amber-950/30 p-6 text-amber-100" role="alert">
      <h3 class="text-lg font-bold">Verification could not run</h3>
      <p class="mt-2 overflow-wrap-anywhere text-amber-200">{{ error }}</p>
      <button type="button" class="mt-4 rounded-lg border border-amber-400/50 px-4 py-2 text-sm font-semibold hover:bg-amber-900/40" @click="runVerification">
        Try again
      </button>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  challengeId: {
    type: String,
    required: true
  }
})

const isVerifying = ref(false)
const result = ref(null)
const error = ref('')

const announcement = computed(() => {
  if (isVerifying.value) return 'Verification is running.'
  if (error.value) return error.value
  if (result.value) return result.value.message
  return ''
})

const statusMessage = (status) => ({
  404: 'This challenge no longer exists. Return to the learning dashboard and choose another challenge.',
  419: 'Your page token expired. Reload this page, then run verification again.',
  500: 'The server could not complete verification. Check the application log, then try again.'
}[status] || `The server returned HTTP ${status}. Try again.`)

const validPayload = (data) => data
  && typeof data === 'object'
  && typeof data.status === 'string'
  && typeof data.message === 'string'
  && Array.isArray(data.tests)

const formatTimestamp = (timestamp) => {
  const date = new Date(timestamp)
  return Number.isNaN(date.getTime()) ? timestamp : new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'medium'
  }).format(date)
}

const runVerification = async () => {
  if (isVerifying.value) return

  isVerifying.value = true
  result.value = null
  error.value = ''

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.content
    if (!token) throw new Error('The page token is missing. Reload this page, then try again.')

    const response = await fetch(`/api/verify/${encodeURIComponent(props.challengeId)}`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token
      }
    })

    const body = await response.text()
    let data = null
    try {
      data = body === '' ? null : JSON.parse(body)
    } catch {
      data = null
    }

    if (!response.ok) {
      if (response.status === 409 && validPayload(data)) {
        result.value = data
        return
      }
      throw new Error(validPayload(data) ? data.message : statusMessage(response.status))
    }

    if (!validPayload(data) || !['pass', 'fail'].includes(data.status)) {
      throw new Error('The server returned an invalid verification result. Reload the page and try again.')
    }

    result.value = data
  } catch (exception) {
    error.value = exception instanceof Error
      ? exception.message
      : 'Verification failed unexpectedly. Try again.'
  } finally {
    isVerifying.value = false
  }
}
</script>
