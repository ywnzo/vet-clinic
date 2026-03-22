<template>
<section class="py-16 flex justify-between items-center gap-8" id="contact">
    <div class="flex flex-col bg-gray-800 p-8 text-white">
        <h2 class="text-3xl font-bold">Find us</h2>
        <span class="mt-2 mb-4">We welcome walk-ins during opening hours.</span>
        <div class="flex flex-col">
            <span class="font-bold">Address</span>
            <span>22 Rue le Brix, 38100 Grenoble</span>
        </div>
        <div class="flex flex-col">
            <span class="font-bold">Phone</span>
            <a href="tel:+33 4 76 00 00 00">+33 4 76 00 00 00</a>
        </div>
        <div class="flex flex-col">
            <span class="font-bold">Email</span>
            <a href="mailto:contact@lumiere-vision.com">contact@lumiere-vision.com</a>
        </div>
        <div class="flex flex-col">
            <span class="font-bold">Hours</span>
            <span>Monday - Friday: 10:00-17:00</span>
        </div>
    </div>

    <div class="flex-1">
        <l-map
          ref="map"
          :zoom="16"
          :center="[45.1794739, 5.7212095]"
          :use-global-leaflet="false"
          style="width: 100%; height: 400px"
        >
          <l-tile-layer
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          ></l-tile-layer>
        </l-map>
    </div>
</section>

</template>

<script setup>
import { ref } from "vue";
import "leaflet/dist/leaflet.css";
import { LMap, LTileLayer } from "@vue-leaflet/vue-leaflet";
</script>
