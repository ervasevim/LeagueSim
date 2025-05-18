import { createRouter, createWebHistory } from 'vue-router';
import Teams from '@/pages/league/teams.vue';

const routes = [
    {
        path: '/teams',
        component: Teams,
    },
];

export default createRouter({
    history: createWebHistory(),
    routes,
});
