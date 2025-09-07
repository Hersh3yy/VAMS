<template>
    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <StatCard
            label="Albums"
            :value="stats.totalAlbums"
            icon="collection"
        />
        <StatCard
            label="Mosaics"
            :value="stats.totalMosaics"
            icon="template"
        />
        <StatCard
            label="Images"
            :value="stats.totalImages"
            icon="photograph"
        />
        <StatCard
            label="Videos"
            :value="stats.totalVideos"
            icon="video"
        />
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
