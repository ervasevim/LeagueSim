<template>
    <div class="max-w-md mx-auto mt-10 p-4">
        <h2 class="text-2xl font-light mb-6">Tournament Teams</h2>

        <table class="w-full border-collapse">
            <thead>
            <tr class="bg-gray-800 text-white">
                <th class="text-left py-2 px-4 font-bold">Team Name</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="team in teams" :key="team.id" class="border-b border-gray-200">
                <td class="py-2 px-4">{{ team.name }}</td>
            </tr>
            </tbody>
        </table>

        <button
            @click="generateFixtures"
            class="mt-6 bg-blue-400 hover:bg-blue-500 text-white py-2 px-4 rounded"
        >
            Generate Fixtures
        </button>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Inertia } from '@inertiajs/inertia';

const teams = ref([]);

const fetchTeams = async () => {
    try {
        const response = await axios.get('/api/teams');
        teams.value = response.data.data;
    } catch (error) {
        console.error('Takımlar alınamadı:', error);
    }
};

const generateFixtures = async () => {
    try {
        await axios.get('/api/fixtures');
        Inertia.visit('/fixtures');
    } catch (error) {
        console.error('Fikstür oluşturulamadı:', error);
    }
};

onMounted(fetchTeams);
</script>
