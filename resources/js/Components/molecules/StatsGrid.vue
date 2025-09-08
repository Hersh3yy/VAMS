<template>
    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <StatCard title="Albums" :value="stats.totalAlbums" />
        <StatCard title="Mosaics" :value="stats.totalMosaics" />
        <StatCard title="Images" :value="stats.totalImages" />
        <StatCard title="Videos" :value="stats.totalVideos" />
    </div>
</template>

<script setup lang="ts">
import StatCard from '@/Components/atoms/StatCard.vue';

interface Stats {
    totalAlbums: number;
    totalMosaics: number;
    totalImages: number;
    totalVideos: number;
}

interface Props {
    stats: Stats;
}

defineProps<Props>();
</script>
