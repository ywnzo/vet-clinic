<template>
    <header class="shadow">
        <div class="max-w-5xl mx-auto p-4 box-border flex items-center justify-between">
            <div class="text-2xl font-semibold">Lumière</div>
            <nav class="text-sm text-gray-600 flex items-center gap-2">
                <div class="flex items-center gap-2 mr-4">
                    <a href="#team" class="mx-2 hover:text-gray-800">Team</a>
                    <a href="#technology" class="mx-2 hover:text-gray-800">Technology</a>
                    <a href="#pricing" class="mx-2 hover:text-gray-800">Pricing</a>
                    <a href="#contact" class="mx-2 hover:text-gray-800">Contact</a>
                </div>

                <div v-if="auth.state.user" class="flex items-center space-x-3">
                    <a
                        href="#booking"
                        :disabled="auth.state.loading"
                        class="px-4 py-2 border text-sm bg-gray-800 text-white hover:bg-gray-700 disabled:opacity-60"
                    > Booking </a>
                </div>

                <div v-else>
                    <button
                        @click="$emit('open-auth')"
                        :disabled="auth.state.loading"
                        class="px-4 py-2 border text-sm bg-gray-800 text-white hover:bg-gray-700 disabled:opacity-60"
                    > Login </button>
                </div>
            </nav>

        </div>

    </header>
</template>

<script setup>
import { computed } from 'vue';
import { useAuth } from '../composables/useAuth';

const auth = useAuth();

async function onLogout() {
  await auth.logout();
}
</script>
