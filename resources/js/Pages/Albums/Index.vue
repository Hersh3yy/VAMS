<template>
    <IndexLayout
        title="Albums"
        :items="albums"
        :create-route="route('albums.create')"
        create-button-text="Create Album"
        empty-state-title="No Albums Yet"
        empty-state-message="Create your first album to get started organizing your images."
        empty-state-button-text="Create First Album"
        :get-item-route="(album) => route('albums.show', album.id)"
    />
</template>

<script setup lang="ts">
import IndexLayout from '@/Components/shared/IndexLayout.vue';
import type { Album } from '@/types/album';

defineProps<{
    albums: Album[];
}>();
</script>