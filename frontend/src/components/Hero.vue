<template>
<section class="py-16 flex items-center justify-center">
    <div class="flex flex-col">
        <span class="italic">Precision Eye Care</span>
        <h1 class="font-bold text-6xl mt-2 mb-8">See the world with clarity & elegance</h1>
        <span>Where advanced ophthalmic science meets refined aesthetic sensibility. Your vision, perfected with artistry.</span>
    </div>

    <div class="w-full inset-x-64 top-0 z-0">
      <canvas ref="heroCanvas" id="hero-canvas" class="w-full h-96 block"></canvas>
    </div>
</section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useEye } from '../composables/useEye';

const heroCanvas = ref(null);
const { eye } = useEye('hero-canvas');

onUnmounted(() => {
  eye.destroy();
});
</script>
