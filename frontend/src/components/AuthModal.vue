<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black opacity-40" @click="close"></div>

    <div class="bg-white shadow-lg w-full max-w-md z-10 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">{{ mode === 'login' ? 'Login' : 'Register' }}</h3>
        <button type="button" @click="close" class="text-gray-500 hover:text-gray-700" aria-label="Close">✕</button>
      </div>

      <form @submit.prevent="submit" class="space-y-4" novalidate>
        <div v-if="mode === 'register'">
            <label class="block text-sm font-medium mb-1">Name*</label>
            <input v-model="form.name" class="w-full border p-2" />
        </div>

        <div v-if="mode === 'register'">
            <label class="block text-sm font-medium mb-1">Surname*</label>
            <input v-model="form.surname" class="w-full border p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Email*</label>
          <input v-model="form.email" type="email" required class="w-full border p-2" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Password*</label>
          <input v-model="form.password" type="password" required class="w-full border p-2" />
        </div>

        <div v-if="mode === 'register'">
            <label class="block text-sm font-medium mb-1">Address</label>
            <input v-model="form.address" type="address" required class="w-full border p-2" placeholder="Optional" />
        </div>

        <div class="flex items-center justify-between">
          <button type="submit" class="px-4 py-2 bg-gray-800 text-white">
            {{ mode === 'login' ? 'Login' : 'Register' }}
          </button>

          <div class="text-sm">
            <button type="button" @click="toggleMode" class="text-gray-800 hover:underline">
              {{ mode === 'login' ? 'Create account' : 'Have an account? Login' }}
            </button>
          </div>
        </div>

        <div v-if="loading" class="text-sm text-gray-500">Working…</div>
        <div v-if="error" class="text-sm text-red-600">{{ error }}</div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useAuth } from '../composables/useAuth'

const emit = defineEmits(['close', 'authenticated'])
const { login, register } = useAuth()

const mode = ref('login')
const loading = ref(false)
const error = ref('')

const form = reactive({
  name: '',
  surname: '',
  email: '',
  password: '',
  address: ''
})

function close() {
  emit('close')
  form.name = ''
  form.surname = ''
  form.email = ''
  form.password = ''
  form.address = ''
  error.value = ''
  loading.value = false
}

function toggleMode() {
  mode.value = mode.value === 'login' ? 'register' : 'login'
  error.value = ''
}

// Debugging helper: logs will show when submit fired
async function submit() {
  console.log('[AuthModal] submit clicked, mode=', mode.value)
  loading.value = true
  error.value = ''
  try {
    if (mode.value === 'login') {
      const r = await login({ email: form.email, password: form.password })
      if (!r.success) throw new Error(r.error || 'Login failed')
    } else {
      const r = await register({ name: form.name, surname: form.surname, email: form.email, password: form.password, address: form.address })
      if (!r.success) throw new Error(r.error || 'Registration failed')
    }
    emit('authenticated')
    close()
  } catch (e) {
    error.value = e.message || 'Auth failed'
  } finally {
    loading.value = false
  }
}
</script>
