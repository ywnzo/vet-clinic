<template>
  <div v-if="date" class="mt-4 p-4 bg-gray-800 shadow">
    <div class="flex items-center justify-between mb-3">
      <div class="font-medium text-white">Slots for {{ format(date, 'dd. MMMM yyyy') }}</div>
      <div>
        <button @click="refresh" class="px-1 py-1 border text-sm bg-white">Refresh</button>
      </div>
    </div>

    <div v-if="availableSlots.length === 0" class="text-sm text-gray-300">
      No available slots for this day.
    </div>

    <div v-else class="grid grid-cols-1 gap-1">
      <div v-for="slot in availableSlots" :key="slot" class="p-1">
        <button
          @click="openBooking(slot)"
          class="w-full p-2 bg-white"
        >
          {{ slot }}
        </button>
      </div>
    </div>

    <BookingModal
      v-if="bookingSlot"
      :date="date"
      :time="bookingSlot"
      @close="bookingSlot = null"
      @booked="onBooked"
    />
  </div>

  <div v-else class="text-sm text-gray-500">Select a date to view slots</div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useAuth } from '../../composables/useAuth'
import { generateSlots } from '../../utils/slotUtils'
import BookingModal from './BookingModal.vue'
import { format} from 'date-fns'

const props = defineProps({
  date: { type: String, default: null },
  officeStart: { type: String, default: '09:00' },
  officeEnd: { type: String, default: '17:00' },
  slotMinutes: { type: Number, default: 60 }
})

const auth = useAuth()
const bookedSet = ref(new Set())
const slots = ref([])
const bookingSlot = ref(null)

const isDateInPast = computed(() => {
  if (!props.date) return false
  const today = new Date().toISOString().slice(0,10)
  return props.date < today
})

const availableSlots = computed(() => {
  if (isDateInPast.value) return []
  return slots.value.filter(slot => {
    const key = slot.slice(0,5) // "HH:MM"
    if (bookedSet.value.has(key)) return false
    if (isPastSlot(slot)) return false
    return true
  })
})

async function load() {
  if (!props.date) return
  slots.value = generateSlots(props.officeStart, props.officeEnd, props.slotMinutes)
  try {
    const res = await auth.authFetch('/api/appointments/search', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ date: props.date })
    })
    if (!res.ok) throw new Error('Failed to load appointments')
    const result = await res.json()
    const appointments = Array.isArray(result?.data) ? result.data : (Array.isArray(result) ? result : [])
    const times = appointments.map(a => (typeof a.time === 'string' ? a.time.slice(0,5) : ''))
    bookedSet.value = new Set(times)
    console.log('[DaySlots] loaded booked times (HH:MM):', Array.from(bookedSet.value))
  } catch (e) {
    console.error('Failed to load appointments', e)
    bookedSet.value = new Set()
  }
}

function refresh() {
  load()
}

function openBooking(time) {
  if (isDateInPast.value) {
    console.log('[DaySlots] cannot open booking for past date', props.date)
    return
  }
  if (isPastSlot(time)) {
    console.log('[DaySlots] cannot open booking for past time', time)
    return
  }
  bookingSlot.value = time
}

function onBooked() {
  console.log('[DaySlots] onBooked - reload')
  bookingSlot.value = null
  load()
}

function isPastSlot(slot) {
  const today = new Date().toISOString().slice(0,10)
  if (props.date !== today) return false
  const now = new Date()
  const [h,m] = slot.split(':').map(Number)
  const slotDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), h, m, 0)
  return slotDate <= now
}

watch(() => props.date, load, { immediate: true })
</script>
