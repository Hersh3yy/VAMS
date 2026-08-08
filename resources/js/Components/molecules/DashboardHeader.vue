<template>
    <div class="page-header">
        <h1 class="page-title">Welcome, {{ userName }}!</h1>
        <div class="flex gap-4">
            <Button variant="primary" as="a" :href="route('albums.create')"> Create Album </Button>
            <Button variant="primary" as="a" :href="route('mosaics.create')">
                Create Mosaic
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import Button from '@/Components/Base/Button.vue';

interface Props {
    userName: string;
}

defineProps<Props>();
</script>
