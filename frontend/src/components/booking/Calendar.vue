<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <button @click="prevMonth" class="px-2 py-1 bg-gray-800 text-white">Prev</button>
      <div class="font-medium">{{ monthLabel }}</div>
      <button @click="nextMonth" class="px-2 py-1 bg-gray-800 text-white">Next</button>
    </div>

    <div class="grid grid-cols-7 gap-1 text-sm">
      <div v-for="d in weekdays" :key="d" class="text-center font-semibold">{{ d }}</div>

      <div v-for="day in daysGrid" :key="day.key" class="h-20 border rounded">
        <button
          @click="select(day.date)"
          :class="[
            'w-full h-full text-left p-1 bg-white',
            { 'bg-gray-800 text-white': isSelected(day.date) },
            { 'text-gray-400': !day.inMonth || day.date < todayStr },
            day.date < todayStr ? 'cursor-not-allowed opacity-60' : 'hover:bg-gray-200 text-gray-800'
          ]"
        >
          <div class="flex items-center justify-center">
              <div class="text-sm font-medium" :class="day.date === todayStr ? 'font-bold text-red-500' : ''">
                {{ day.label }}
            </div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { format, startOfMonth, endOfMonth, startOfWeek, endOfWeek, addDays, addMonths } from 'date-fns'

const emit = defineEmits(['date-selected'])

const now = ref(new Date())
const selected = ref(null)

const monthStart = computed(() => startOfMonth(now.value))
const monthEnd = computed(() => endOfMonth(now.value))

const monthLabel = computed(() => format(now.value, 'MMMM yyyy'))

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

const todayStr = computed(() => format(new Date(), 'yyyy-MM-dd'))

const daysGrid = computed(() => {
  const start = startOfWeek(monthStart.value, { weekStartsOn: 1 })
  const end = endOfWeek(monthEnd.value, { weekStartsOn: 1 })
  const days = []
  let current = start
  while (current <= end) {
    days.push({
      key: format(current, 'yyyy-MM-dd'),
      date: format(current, 'yyyy-MM-dd'),
      label: format(current, 'd'),
      inMonth: current >= monthStart.value && current <= monthEnd.value,
    })
    current = addDays(current, 1)
  }
  return days
})

function select(date) {
  // prevent selecting past dates
  if (date < todayStr.value) {
    // optionally show a toast or console
    console.log('[Calendar] ignored selection of past date', date)
    return
  }
  selected.value = date
  emit('date-selected', date)
}

function isSelected(date) {
  return selected.value === date
}

function prevMonth() {
  now.value = addMonths(now.value, -1)
}

function nextMonth() {
  now.value = addMonths(now.value, 1)
}
</script>
