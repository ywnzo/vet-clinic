<template>
    <div class="min-h-screen flex flex-col">
        <Header @open-auth="showAuthModal = true"/>

        <main class="max-w-5xl w-full mx-auto p-4 flex-1 box-border">

            <Hero />

            <div class="border-t border-gray-800 z-10"></div>

            <Team />

            <div class="border-t border-gray-800 z-10"></div>

            <Technology />

            <div class="border-t border-gray-800 z-10"></div>

            <Pricing />

            <div class="border-t border-gray-800 z-10"></div>

            <div class="py-16 flex flex-col" id="booking">
                <h2 class="text-3xl font-bold mb-4">Booking</h2>
                <div v-if="auth.state.user" class="py-16 grid grid-cols-2 md:grid-cols-2 gap-6">
                    <div class="flex-initial">
                        <Calendar @date-selected="onDateSelected" />
                    </div>

                    <div class="flex-1">
                        <DaySlots :date="selectedDate" />
                    </div>
                </div>
                <div v-else>
                    <p>Please log in to book an appointment.</p>
                </div>
            </div>

            <div class="border-t border-gray-800 z-10"></div>

            <Contact />

        </main>

        <Footer />

        <AuthModal v-if="showAuthModal" @close="handleModalClose" @authenticated="onAuthentication" />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Header from './components/Header.vue';
import Hero from './components/Hero.vue';
import Footer from './components/Footer.vue';
import Team from './components/Team.vue';
import Technology from './components/Technology.vue';
import Pricing from './components/Pricing.vue';
import Contact from './components/Contact.vue';

import AuthModal from './components/AuthModal.vue';
import Calendar from './components/booking/Calendar.vue';
import DaySlots from './components/booking/DaySlots.vue';

import { useAuth } from './composables/useAuth';

const showAuthModal = ref(false);
const initializing = ref(true);
const auth = useAuth();
const selectedDate = ref(null);

function handleModalClose() {
  showAuthModal.value = false;
}

async function onAuthentication() {
  showAuthModal.value = false;
}

async function onLogout() {
  await auth.logout();
}

function onDateSelected(date) {
  selectedDate.value = date;
}

onMounted(async () => {
  try {
    const res = await auth.refresh();
    console.log('Refresh result', res);
  } catch (err) {
    console.warn('refresh threw: ', err);
  } finally {
    initializing.value = false;
  }
})


</script>
