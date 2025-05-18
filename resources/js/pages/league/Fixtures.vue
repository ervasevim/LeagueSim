<template>
    <div class="flex flex-wrap gap-4">
        <div
            v-for="(games, week) in groupedFixtures"
            :key="week"
            class="bg-gray-800 text-white p-4 rounded w-[220px]"
        >
            <h2 class="font-bold mb-2">Week {{ week }}</h2>
            <div
                v-for="game in games"
                :key="game.id"e
                class="bg-white text-black px-2 py-1 mb-1 rounded"
            >
                {{ game.home_team.name }} - {{ game.away_team.name }}
            </div>
        </div>
        <div class="w-full mt-4">
            <button
                @click="startSimulation"
                class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded"
            >
                Start Simulation
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { Inertia } from '@inertiajs/inertia';

const fixtures = ref([])

const fetchFixtures = async () => {
    try {
        const response = await axios.get('/api/fixtures')
        fixtures.value = response.data.data
    } catch (error) {
        console.error('Failed to fetch fixtures', error)
    }
}

onMounted(fetchFixtures)

const groupedFixtures = computed(() => {
    const groups = {}
    for (const fixture of fixtures.value) {
        if (!groups[fixture.week]) {
            groups[fixture.week] = []
        }
        groups[fixture.week].push(fixture)
    }

    return Object.fromEntries(
        Object.entries(groups).sort(([a], [b]) => a - b)
    )
})


const startSimulation = async () => {
    try {
        Inertia.visit('/simulation');
    } catch (error) {
        console.error('Fikstür oluşturulamadı:', error);
    }
};

</script>

<style scoped>
/* Opsiyonel stil özelleştirmeleri */
</style>
