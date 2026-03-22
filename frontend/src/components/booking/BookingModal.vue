<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-40" @click.self="close"></div>

    <div class="bg-white shadow-lg w-full max-w-sm z-10 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Confirm booking</h3>
        <button type="button" @click="close" class="text-gray-500">✕</button>
      </div>

      <div class="mb-4">
        <div class="text-sm">Date: <span class="font-medium">{{ date }}</span></div>
        <div class="text-sm">Time: <span class="font-medium">{{ time.slice(0,5) }}</span></div>
      </div>

      <div class="flex items-center space-x-2">
        <button @click="confirm" :disabled="loading" class="px-4 py-2 bg-gray-800 text-white">Confirm</button>
        <button @click="close" :disabled="loading" class="px-4 py-2 border">Cancel</button>
      </div>

      <div v-if="error" class="text-sm text-red-600 mt-3">{{ error }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuth } from '../../composables/useAuth'

const props = defineProps({
  date: { type: String, required: true },
  time: { type: String, required: true }
})

const emit = defineEmits(['close', 'booked'])
const auth = useAuth()
const loading = ref(false)
const error = ref('')

function close() {
  emit('close')
}

async function confirm() {
  loading.value = true
  error.value = ''
  try {
    const res = await auth.authFetch('/api/appointments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ date: props.date, time: props.time })
    })
    console.log(res)
    if (!res.ok) {
      const text = await res.text()
      throw new Error(text || `Status ${res.status}`)
    }
    await res.json()
    emit('booked')
    emit('close')
  } catch (e) {
    console.error('Booking failed', e)
    // try to parse message
    try {
      const parsed = JSON.parse(e.message)
      error.value = parsed.message || parsed.error || e.message
    } catch {
      error.value = e.message || 'Booking failed'
    }
  } finally {
    loading.value = false
  }
}
</script>
