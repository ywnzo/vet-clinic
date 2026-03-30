<template>
<section class="py-16 flex flex-col" id="pricing">
    <h2 class="text-3xl font-bold">Pricing</h2>
    <div class="my-8 grid grid-cols-1 md:grid-cols-1 gap-4">
        <div class="flex flex-col" v-for="price in prices" :key="price.category">
            <span class="font-bold text-2xl mb-4">{{ price.category }}</span>

            <table class="min-w-full table-fixed border-collapse border border-gray-800">
                <colgroup>
                    <col class="w-[60%]">
                    <col class="w-[20%]">
                    <col class="w-[20%]">
                </colgroup>

                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Note</th>
                        <th class="px-4 py-2 text-right">Price</th>
                    </tr>
                </thead>


                <tbody class="divide-y divide-gray-800">
                    <tr v-for="item in price.items" :key="item.name" class="p-2">
                        <td class="px-4 py-2 bg-white">{{ item.name }}</td>
                        <td class="px-4 py-2 text-gray-600 italic bg-white">{{ item.note }}</td>
                        <td class="px-4 py-2 font-bold text-right bg-white">{{ item.price }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
</template>

<script>
export default {
    data() {
        return {
            prices: [
              { category: 'Consultations', items: [
                {name:'Standard Eye Examination', note:'~45 min',        price:'€ 65'},
                {name:'Contact Lens Fitting',     note:'incl. trial pair', price:'€ 50'},
                {name:'Glaucoma Screening',       note:'OCT + IOP',      price:'€ 80'},
                {name:'Paediatric Assessment',    note:'3–16 years',     price:'€ 70'},
              ]},
              { category: 'Eyewear', items: [
                {name:'Single vision lenses',      note:'from',      price:'€ 45'},
                {name:'Progressive lenses',        note:'from',      price:'€ 149'},
                {name:'Anti-reflection coating',   note:'per lens',  price:'€ 20'},
                {name:'Blue-light filter',         note:'add-on',    price:'€ 25'},
              ]},
              { category: 'Contact Lenses (monthly)', items: [
                {name:'Daily disposables',   note:'30 pairs',  price:'€ 35'},
                {name:'Monthly lenses',      note:'6 lenses',  price:'€ 28'},
                {name:'Toric (astigmatism)', note:'6 lenses',  price:'€ 48'},
                {name:'Multifocal',          note:'6 lenses',  price:'€ 58'},
              ]},
            ]
        };
    }
};
</script>
