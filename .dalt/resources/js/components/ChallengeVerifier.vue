<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold mb-2">Verify Your Solution</h2>
      <p class="text-gray-600">
        Run the automated tests to check if you've fixed all the bugs correctly.
      </p>
    </div>

    <!-- Verify Button -->
    <button
      @click="runVerification"
      :disabled="isVerifying"
      class="px-6 py-3 bg-[#3E5F44] text-white rounded-lg hover:bg-[#2d4532] transition-all disabled:opacity-50 disabled:cursor-not-allowed font-medium flex items-center gap-2"
    >
      <svg v-if="isVerifying" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span v-if="isVerifying">Running Tests...</span>
      <span v-else>🧪 Run Verification</span>
    </button>

    <!-- Results -->
    <div v-if="result" class="mt-6">
      <!-- Success -->
      <div v-if="result.success" class="bg-green-50 border-2 border-green-500 rounded-xl p-6">
        <div class="flex items-start gap-4">
          <div class="text-4xl">✅</div>
          <div class="flex-1">
            <h3 class="text-xl font-bold text-green-900 mb-2">All Tests Passed!</h3>
            <p class="text-green-800 mb-4">{{ result.message }}</p>
            <div class="text-sm text-green-700">
              Verified at {{ result.timestamp }}
            </div>
          </div>
        </div>

        <!-- Test Details -->
        <div v-if="result.tests && result.tests.length > 0" class="mt-4 pt-4 border-t border-green-200">
          <div class="space-y-2">
            <div v-for="(test, index) in result.tests" :key="index" class="flex items-center gap-2 text-sm text-green-800">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>{{ test.description || test.name }}</span>
            </div>
          </div>
        </div>

        <!-- Next Steps -->
        <div class="mt-6 pt-4 border-t border-green-200">
          <p class="text-green-900 font-medium mb-3">🎉 Great job! What's next?</p>
          <div class="flex gap-3">
            <a href="/learn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
              Try Another Challenge
            </a>
          </div>
        </div>
      </div>

      <!-- Failure -->
      <div v-else class="bg-red-50 border-2 border-red-500 rounded-xl p-6">
        <div class="flex items-start gap-4">
          <div class="text-4xl">❌</div>
          <div class="flex-1">
            <h3 class="text-xl font-bold text-red-900 mb-2">Tests Failed</h3>
            <p class="text-red-800 mb-4">{{ result.message }}</p>
            <div class="text-sm text-red-700">
              Tested at {{ result.timestamp }}
            </div>
          </div>
        </div>

        <!-- Failed Tests -->
        <div v-if="result.tests && result.tests.length > 0" class="mt-4 pt-4 border-t border-red-200">
          <p class="text-sm font-medium text-red-900 mb-3">Test Results:</p>
          <div class="space-y-3">
            <div v-for="(test, index) in result.tests" :key="index" 
                 :class="test.passed ? 'bg-green-50 border-green-200' : 'bg-red-100 border-red-300'"
                 class="border rounded-lg p-3">
              <div class="flex items-start gap-2">
                <svg v-if="test.passed" class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <svg v-else class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <div class="flex-1">
                  <p :class="test.passed ? 'text-green-900' : 'text-red-900'" class="font-medium text-sm">
                    {{ test.description || test.name }}
                  </p>
                  <p v-if="test.hint && !test.passed" class="text-red-700 text-sm mt-1">
                    💡 Hint: {{ test.hint }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tips -->
        <div class="mt-6 pt-4 border-t border-red-200">
          <p class="text-red-900 font-medium mb-3">💡 Tips:</p>
          <ul class="space-y-2 text-sm text-red-800">
            <li class="flex items-start gap-2">
              <span>•</span>
              <span>Read the challenge description and hints carefully</span>
            </li>
            <li class="flex items-start gap-2">
              <span>•</span>
              <span>Check the related lesson for guidance</span>
            </li>
            <li class="flex items-start gap-2">
              <span>•</span>
              <span>Look at the test results above for specific issues</span>
            </li>
            <li class="flex items-start gap-2">
              <span>•</span>
              <span>Run verification again after making changes</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="mt-6 bg-yellow-50 border border-yellow-300 rounded-xl p-6">
      <div class="flex items-start gap-4">
        <div class="text-3xl">⚠️</div>
        <div>
          <h3 class="text-lg font-bold text-yellow-900 mb-2">Verification Error</h3>
          <p class="text-yellow-800">{{ error }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  challengeId: {
    type: String,
    required: true
  }
});

const isVerifying = ref(false);
const result = ref(null);
const error = ref(null);

const runVerification = async () => {
  isVerifying.value = true;
  result.value = null;
  error.value = null;

  try {
    const response = await fetch(`/api/verify/${props.challengeId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    result.value = data;
  } catch (e) {
    error.value = `Failed to run verification: ${e.message}. Make sure the PHP server is running.`;
  } finally {
    isVerifying.value = false;
  }
};
</script>
