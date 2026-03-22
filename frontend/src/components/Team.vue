<template>
<section class="py-16 flex flex-col" id="team">
    <h2 class="text-3xl font-bold">Our team</h2>
    <div class="my-8 grid grid-cols-3 md:grid-cols-3 gap-4">
        <div class="bg-gray-800 p-6 text-white flex flex-col" v-for="member in teamMembers" :key="member.id">
            <img :src="member.image" alt="" class="w-full h-auto" />
            <span class="font-bold">{{ member.name }}</span>
            <span class="mb-2">{{ member.role }}</span>
            <span class="italic">{{ member.quote }}</span>
        </div>
    </div>

    <h3 class="text-2xl font-bold mb-2">Specialists who care</h3>
    <span>Our multidisciplinary team combines decades of clinical experience with a genuine passion for personalised care.</span>
</section>
</template>

<script>
export default {
    data() {
        return {
            teamMembers: [
              { id: 1, name: 'Dr. Jiří Kachlička', role: 'Ophthalmologist', quote: 'Every pair of eyes tells a unique story.', image: '' },
              { id: 1, name: 'Dr. Zdena Kachličková', role: 'Optometrist', quote: 'Precision and empathy go hand in hand.', image: '' },
              { id: 1, name: 'Zdenek Rohlík', role: 'Orthoptist', quote: 'I help children see the world clearly.', image: '' },
            ]
        }
    }
}
</script>
