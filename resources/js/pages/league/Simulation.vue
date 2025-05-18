<template>
    <div class="p-6 space-y-6">
        <!-- Başlık -->
        <h1 class="text-2xl font-bold text-center">Simulation</h1>

        <!-- Ana 3 panel -->
        <div class="grid grid-cols-3 gap-4">
            <!-- Takım tablosu -->
            <div>
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-gray-800 text-white">
                        <th class="p-2">Team Name</th>
                        <th class="p-2">P</th>
                        <th class="p-2">W</th>
                        <th class="p-2">D</th>
                        <th class="p-2">L</th>
                        <th class="p-2">GD</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="team in standings" :key="team.name" class="border-b">
                        <td class="p-2">{{ team.name }}</td>
                        <td class="p-2">{{ team.played }}</td>
                        <td class="p-2">{{ team.wins }}</td>
                        <td class="p-2">{{ team.draws }}</td>
                        <td class="p-2">{{ team.losses }}</td>
                        <td class="p-2">{{ team.goal_difference }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Fikstür -->
            <div>
                <h2 class="bg-gray-800 text-white p-2 font-semibold">Week {{ currentWeek }}</h2>
                <div v-for="match in fixtures" :key="match.id" class="p-2 border-b">
                    {{ match.home_team.name }} - {{ match.away_team.name }}
                </div>
            </div>

            <!-- Şampiyonluk yüzdeleri -->
            <div>
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-gray-800 text-white p-2 font-semibold">
                        <th class="p-2">Championship Predictions</th>
                        <th class="p-2">%</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="team in predictions" :key="team.name" class=" border-b">
                        <td class="p-2">{{ team.name }}</td>
                        <td class="p-2">{{ team.chance }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Butonlar -->
        <div class="flex justify-center gap-6">
            <button
                class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded"
                @click="playAllWeeks"
            >
                Play All Weeks
            </button>
            <button
                class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded"
                @click="playNextWeek"
            >
                Play Next Week
            </button>
            <button
                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                @click="resetData"
            >
                Reset Data
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { controlledRef } from '@vueuse/core';

const standings = ref([])
const fixtures = ref([])
const predictions = ref([])
const currentWeek = ref(1)

const fetchAll = async () => {
    await fetchCurrentWeek()
    await Promise.all([fetchStandings(), fetchFixtures(), fetchPredictions()])
}
const fetchCurrentWeek = async () => {
    const res = await axios.get('/api/current_week')
    currentWeek.value = res.data.data
}

const fetchStandings = async () => {
    const res = await axios.get('/api/standings')
     console.log(res.data)
    standings.value = res.data
}

const fetchFixtures = async () => {
    const res = await axios.get(`/api/fixtures?week=${currentWeek.value}`)
    fixtures.value = res.data.data
}

const fetchPredictions = async () => {
    const res = await axios.get('/api/predictions')
    predictions.value = res.data.data
}

const playAllWeeks = async () => {
    await axios.get('/api/play-all-weeks')
    await fetchAll()
}

const playNextWeek = async () => {
    await axios.get('/api/play-next-week')
    if (currentWeek.value < 6) {
        currentWeek.value++
    }
    await fetchAll()
}

const resetData = async () => {
    await axios.get('/api/reset')
    currentWeek.value = 1
    await fetchAll()
}

onMounted(fetchAll)
</script>
