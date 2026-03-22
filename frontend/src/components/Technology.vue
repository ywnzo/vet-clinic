<template>
<section class="py-16 flex flex-col" id="technology">
    <h2 class="text-3xl font-bold">Innovation</h2>
    <div class="mt-8 grid grid-cols-3 md:grid-cols-2 gap-4">
        <div class="bg-gray-800 p-6 text-white flex flex-col" v-for="tech in technology" :key="tech.id">
            <span class="">{{ tech.cat }}</span>
            <span class="font-bold">{{ tech.name }}</span>
            <span class="mt-2">{{ tech.desc }}</span>
        </div>
    </div>
</section>
</template>

<script>
export default {
    data() {
        return {
            technology: [
              {cat:'Imaging',    name:'OCT Angiography',      desc:'Non-invasive 3D cross-sectional imaging of the retina and optic nerve, detecting pathology at the earliest cellular level.'},
              {cat:'Refraction', name:'NIDEK ARK-1',           desc:'Auto-refractometer and keratometer delivering repeatable, accurate measurements for precise prescription determination.'},
              {cat:'Tonometry',  name:'Goldmann Applanation',  desc:'Gold-standard intraocular pressure measurement, essential for glaucoma screening and ongoing monitoring.'},
              {cat:'Topography', name:'Pentacam HR',            desc:'Rotating Scheimpflug camera providing complete 3D analysis of the anterior eye segment.'},
              {cat:'Retinal',    name:'Fundus Photography',     desc:'Wide-field retinal imaging with ultra-high resolution for AI-assisted diabetic retinopathy screening.'},
              {cat:'Dry Eye',    name:'Oculus Keratograph',     desc:'Non-invasive tear film analysis and meibomian gland imaging for comprehensive dry eye diagnosis.'},
            ]
        }
    }
}
</script>
