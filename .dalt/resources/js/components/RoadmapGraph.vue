<template>
  <div>
    <div v-if="error" class="rounded-lg border border-red-500/50 bg-red-950/30 p-4 text-red-100" role="alert">
      <p class="font-medium">The roadmap graph could not be rendered</p>
      <p class="mt-1 text-sm text-red-200">{{ error }}</p>
    </div>

    <div v-else-if="nodes.length === 0" class="rounded-lg border border-amber-500/50 bg-amber-950/30 p-4 text-amber-100" role="status">
      <p>No roadmap nodes are available yet.</p>
    </div>

    <div v-else>
      <div class="mb-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-500">
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400" aria-hidden="true"></span>Verified by a passed challenge</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#93DA97]" aria-hidden="true"></span>Marked understood (self-reported)</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full border border-gray-500" aria-hidden="true"></span>Ready</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-gray-700" aria-hidden="true"></span>Needs prerequisites first</span>
      </div>

      <div ref="graphContainer" class="relative overflow-x-auto rounded-xl border border-gray-800 bg-[#0d1117] p-4 sm:p-6">
        <svg
          class="pointer-events-none absolute left-0 top-0"
          :width="containerSize.width"
          :height="containerSize.height"
          aria-hidden="true"
        >
          <path
            v-for="edge in edges"
            :key="edge.id"
            :d="edge.d"
            fill="none"
            :stroke="edge.strokeColor"
            stroke-width="2"
          />
        </svg>

        <div v-for="section in sections" :key="section.id" class="relative mb-8 last:mb-0">
          <h3 class="mb-3 font-mono text-[10px] font-semibold uppercase tracking-wider text-gray-500">{{ section.label }}</h3>
          <div class="flex flex-wrap gap-6">
            <button
              v-for="node in section.nodes"
              :key="node.id"
              :ref="(el) => setCardRef(node.id, el)"
              type="button"
              class="relative h-[84px] w-44 shrink-0 rounded-lg border p-3 text-left text-sm transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#93DA97]"
              :class="cardClass(node)"
              :aria-label="`Lesson ${node.order}: ${node.title} — ${statusLabel(node)}${hasCrossBranchPrerequisite(node) ? ', has a prerequisite in another branch' : ''}`"
              @click="openNode(node)"
            >
              <span
                v-if="hasCrossBranchPrerequisite(node)"
                class="absolute -right-1.5 -top-1.5 flex h-4 w-4 items-center justify-center rounded-full border border-gray-700 bg-[#161b22] text-[9px] text-gray-400"
                aria-hidden="true"
              >⤴</span>
              <span class="block font-mono text-[10px] uppercase tracking-wider text-gray-500">Lesson {{ node.order }}</span>
              <span class="mt-0.5 block truncate font-semibold leading-tight">{{ node.title }}</span>
              <span class="mt-1.5 block text-[11px] font-medium" :class="statusTextClass(node)">{{ statusLabel(node) }}</span>
            </button>
          </div>
        </div>
      </div>

      <p class="mt-3 text-xs text-gray-600">
        Click a node to open its lesson or practice challenge. A <span aria-hidden="true">⤴</span> badge means a
        prerequisite lives in another branch — open the node to see it.
      </p>
    </div>

    <!-- Detail drawer -->
    <div v-if="selected" class="fixed inset-0 z-50 flex justify-end bg-black/60" @click.self="closeNode">
      <div
        ref="drawer"
        class="flex h-full w-full max-w-lg flex-col overflow-y-auto border-l border-gray-800 bg-[#161b22] p-6 shadow-2xl"
        role="dialog"
        aria-modal="true"
        :aria-label="`${selected.id}: ${selected.title}`"
        tabindex="-1"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <span class="font-mono text-xs uppercase tracking-wider text-gray-500">Lesson {{ selected.order }}</span>
            <h3 class="mt-1 text-xl font-bold text-gray-50">{{ selected.title }}</h3>
          </div>
          <button
            ref="closeButton"
            type="button"
            class="rounded-lg border border-gray-700 p-2 text-gray-400 hover:border-gray-500 hover:text-gray-200"
            aria-label="Close node details"
            @click="closeNode"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="mt-2" :class="statusTextClass(selected)">
          <span class="text-sm font-medium">{{ statusLabel(selected) }}</span>
        </div>

        <div class="mt-5 space-y-5 text-sm">
          <section>
            <p class="text-gray-400">{{ selected.description }}</p>
          </section>

          <section v-if="selected.prerequisites.length">
            <h4 class="font-semibold text-gray-200">Prerequisites</h4>
            <div class="mt-2 flex flex-wrap gap-2">
              <button
                v-for="prerequisiteId in selected.prerequisites"
                :key="prerequisiteId"
                type="button"
                class="rounded-md border border-gray-700 px-2 py-1 text-xs text-gray-300 hover:border-[#93DA97]/50 hover:text-[#93DA97]"
                @click="openNode(byId[prerequisiteId])"
              >
                {{ byId[prerequisiteId]?.title ?? prerequisiteId }}
              </button>
            </div>
          </section>

          <a
            :href="`/learn/lessons/${selected.id}`"
            class="flex items-center justify-between rounded-lg border border-[#93DA97]/30 bg-[#93DA97]/10 px-4 py-3 text-sm font-semibold text-[#93DA97] hover:bg-[#93DA97]/20"
          >
            <span>Open lesson — read, trace, and its Laravel bridge</span>
            <span aria-hidden="true">→</span>
          </a>

          <section v-if="selected.challenge_id" class="rounded-lg border border-gray-800 bg-[#0d1117] p-4">
            <h4 class="font-semibold text-gray-200">Practice challenge</h4>
            <p class="mt-1 text-gray-400">{{ selected.challenge_title }}</p>
            <a
              :href="`/learn/challenges/${selected.challenge_id}`"
              class="mt-3 inline-flex items-center gap-1 rounded-lg bg-[#3E5F44] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#4b7253]"
            >
              {{ selected.verified ? 'Review challenge' : 'Open challenge' }}
              <span aria-hidden="true">→</span>
            </a>
          </section>

          <section v-if="!selected.verified" class="border-t border-gray-800 pt-4">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold transition-colors"
              :class="isSelfMarked(selected.id)
                ? 'border-[#93DA97]/50 bg-[#93DA97]/10 text-[#93DA97]'
                : 'border-gray-700 text-gray-300 hover:border-gray-500'"
              @click="toggleSelfMarked(selected.id)"
            >
              <svg v-if="isSelfMarked(selected.id)" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              {{ isSelfMarked(selected.id) ? 'Marked understood' : 'Mark as understood' }}
            </button>
            <p class="mt-2 text-[11px] text-gray-600">Self-reported — stored only in this browser, not proof like a passed challenge.</p>
          </section>
          <section v-else class="border-t border-gray-800 pt-4">
            <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Verified by a passed challenge
            </p>
          </section>
        </div>
      </div>
    </div>

    <div class="sr-only" role="status" aria-live="polite">{{ announcement }}</div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

const STORAGE_KEY = 'dalt.roadmap.selfMarked'

const SECTION_LABELS = {
  foundation: 'Foundation',
  docker: 'Docker branch',
  postgres: 'PostgreSQL branch',
  operations: 'Operations',
}
const SECTION_ORDER = ['foundation', 'docker', 'postgres', 'operations']

const nodes = ref([])
const error = ref('')
const selected = ref(null)
const selfMarked = ref(new Set())
const announcement = ref('')
const drawer = ref(null)
const closeButton = ref(null)
let lastFocused = null

// Cards flow in normal document layout (flex-wrap) so the graph is bounded by the
// viewport and grows downward, not sideways. Edge positions are measured from the
// rendered DOM rather than computed from column/row math, so wrapping "just works".
const graphContainer = ref(null)
const cardRects = ref({})
const containerSize = ref({ width: 0, height: 0 })
const cardRefs = {}
let resizeObserver = null
let measureFrame = null

onMounted(() => {
  try {
    const dataElement = document.getElementById('roadmap-graph-data')
    if (dataElement) {
      const parsed = JSON.parse(dataElement.textContent)
      nodes.value = Array.isArray(parsed) ? parsed : []
    } else {
      error.value = 'The roadmap data is missing. Reload the page or return to the learning dashboard.'
    }
  } catch (e) {
    error.value = `The roadmap data is invalid: ${e.message}`
  }

  try {
    const stored = window.localStorage.getItem(STORAGE_KEY)
    if (stored) {
      selfMarked.value = new Set(JSON.parse(stored))
    }
  } catch {
    // localStorage may be unavailable (private mode, disabled storage); self-marking degrades to session-only.
  }

  nextTick(() => {
    scheduleMeasure()
    if (graphContainer.value && 'ResizeObserver' in window) {
      resizeObserver = new ResizeObserver(scheduleMeasure)
      resizeObserver.observe(graphContainer.value)
    } else {
      window.addEventListener('resize', scheduleMeasure)
    }
  })

  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  if (resizeObserver) {
    resizeObserver.disconnect()
  } else {
    window.removeEventListener('resize', scheduleMeasure)
  }
  if (measureFrame) cancelAnimationFrame(measureFrame)
})

const byId = computed(() => Object.fromEntries(nodes.value.map((node) => [node.id, node])))

const sections = computed(() => {
  const groups = {}
  for (const node of nodes.value) {
    (groups[node.section] ??= []).push(node)
  }
  return SECTION_ORDER
    .filter((id) => (groups[id]?.length ?? 0) > 0)
    .map((id) => ({
      id,
      label: SECTION_LABELS[id] ?? id,
      // Within a section, order by lesson order so wrapped rows still read left-to-right, top-to-bottom.
      nodes: [...groups[id]].sort((a, b) => a.order - b.order),
    }))
})

const setCardRef = (id, el) => {
  if (el) {
    cardRefs[id] = el
  } else {
    delete cardRefs[id]
  }
}

const measure = () => {
  const container = graphContainer.value
  if (!container) return
  const containerRect = container.getBoundingClientRect()
  const next = {}
  for (const node of nodes.value) {
    const el = cardRefs[node.id]
    if (!el) continue
    const rect = el.getBoundingClientRect()
    next[node.id] = {
      left: rect.left - containerRect.left,
      top: rect.top - containerRect.top,
      width: rect.width,
      height: rect.height,
    }
  }
  cardRects.value = next
  containerSize.value = { width: container.scrollWidth, height: container.scrollHeight }
}

const scheduleMeasure = () => {
  if (measureFrame) cancelAnimationFrame(measureFrame)
  measureFrame = requestAnimationFrame(measure)
}

const hasCrossBranchPrerequisite = (node) => node.prerequisites.some((id) => byId.value[id]?.section !== node.section)

const isCompleted = (node) => node.verified || selfMarked.value.has(node.id)

const isAvailable = (node) => node.prerequisites.every((id) => {
  const prerequisite = byId.value[id]
  return prerequisite ? isCompleted(prerequisite) : false
})

const nodeStatus = (node) => {
  if (isCompleted(node)) return 'done'
  return isAvailable(node) ? 'available' : 'locked'
}

const statusLabel = (node) => {
  const status = nodeStatus(node)
  if (status === 'done') return node.verified ? 'Verified ✓' : 'Understood ✓'
  if (status === 'available') return 'Ready'
  return 'Needs prerequisites first'
}

const statusTextClass = (node) => {
  const status = nodeStatus(node)
  if (status === 'done') return node.verified ? 'text-emerald-400' : 'text-[#93DA97]'
  if (status === 'available') return 'text-gray-300'
  return 'text-gray-600'
}

const cardClass = (node) => {
  const status = nodeStatus(node)
  if (status === 'done') {
    return node.verified
      ? 'border-emerald-500/50 bg-emerald-500/10 text-gray-100 hover:bg-emerald-500/20'
      : 'border-[#93DA97]/50 bg-[#93DA97]/10 text-gray-100 hover:bg-[#93DA97]/20'
  }
  if (status === 'available') {
    return 'border-gray-700 bg-[#161b22] text-gray-200 hover:border-[#93DA97]/60 hover:bg-[#1a202c]'
  }
  return 'border-gray-800 bg-[#0f1117]/70 text-gray-500 opacity-70 hover:opacity-100'
}

const edges = computed(() => {
  const result = []
  for (const node of nodes.value) {
    for (const prerequisiteId of node.prerequisites) {
      const prerequisite = byId.value[prerequisiteId]
      if (!prerequisite) continue
      // Cross-branch prerequisites (e.g. a foundation lesson pulling in Postgres) sit in a
      // different section band, often several rows away. Drawing that line means it cuts
      // straight across unrelated cards in between, which reads as noise rather than a
      // connection. Those prerequisites are still listed — and clickable — in the drawer;
      // only same-section edges (adjacent, or a couple of rows apart at most) get drawn.
      if (prerequisite.section !== node.section) continue

      const from = cardRects.value[prerequisiteId]
      const to = cardRects.value[node.id]
      if (!from || !to) continue

      const x1 = from.left + from.width
      const y1 = from.top + from.height / 2
      const x2 = to.left
      const y2 = to.top + to.height / 2
      // Falls back to a fixed offset when a wrapped row puts the target to the left
      // of its prerequisite; the curve still reads as a connector, just looping back.
      const dx = Math.max((x2 - x1) / 2, 24)

      let strokeColor = '#374151' // gray-700, locked
      if (isCompleted(node)) {
        strokeColor = node.verified ? '#34d399' : '#93DA97' // emerald-400 / accent
      } else if (isAvailable(node)) {
        strokeColor = '#4b5563' // gray-600
      }

      result.push({
        id: `${prerequisiteId}->${node.id}`,
        d: `M ${x1} ${y1} C ${x1 + dx} ${y1}, ${x2 - dx} ${y2}, ${x2} ${y2}`,
        strokeColor,
      })
    }
  }
  return result
})

const isSelfMarked = (id) => selfMarked.value.has(id)

const persistSelfMarked = () => {
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify([...selfMarked.value]))
  } catch {
    // Best-effort; state still holds for the current page session.
  }
}

const toggleSelfMarked = (id) => {
  const next = new Set(selfMarked.value)
  if (next.has(id)) {
    next.delete(id)
    announcement.value = `${id} unmarked.`
  } else {
    next.add(id)
    announcement.value = `${id} marked as understood.`
  }
  selfMarked.value = next
  persistSelfMarked()
}

const openNode = (node) => {
  if (!node) return
  lastFocused = document.activeElement
  selected.value = node
  nextTick(() => closeButton.value?.focus())
}

const closeNode = () => {
  selected.value = null
  if (lastFocused instanceof HTMLElement) {
    lastFocused.focus()
  }
}

const onKeydown = (event) => {
  if (event.key === 'Escape' && selected.value) {
    closeNode()
  }
}
</script>
