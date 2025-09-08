<template>
    <ContentGrid
        :items="albums"
        :create-route="createRoute"
        :get-item-route="getItemRoute"
        empty-title="No Albums Yet"
        empty-message="Create your first album to get started organizing your images."
        empty-button-text="Create First Album"
    >
        <template #actions="{ item }">
            <ActionButtons v-if="actions" :actions="getSimpleActions(item)" />
        </template>

        <template #content="{ item }">
            <h3 class="text-lg font-semibold dark:text-yellow-200">
                {{ item.title }}
            </h3>
            <p class="mt-2 text-gray-600 dark:text-yellow-400">
                {{ item.description || 'No description' }}
            </p>
            <div class="mt-2 text-sm text-gray-500">
                {{ getAlbumItemCount(item) }}
            </div>
        </template>
    </ContentGrid>
</template>

<script setup lang="ts">
import ActionButtons from '@/Components/molecules/ActionButtons.vue';
import ContentGrid from '@/Components/organisms/ContentGrid.vue';
import type { Album } from '@/types/album';

interface AlbumAction {
    label: string;
    handler: (album: Album) => void;
    icon:
        | 'arrow-left'
        | 'video'
        | 'play-circle'
        | 'play-circle-outline'
        | 'trash'
        | 'x'
        | 'exclamation-triangle'
        | 'edit';
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
}

interface Props {
    albums: Album[];
    createRoute: string;
    getItemRoute: (album: Album) => string;
    actions?: (album: Album) => AlbumAction[];
}

const props = defineProps<Props>();

const getSimpleActions = (album: Album) => {
    if (!props.actions) return [];

    return props.actions(album).map(action => ({
        label: action.label,
        handler: () => action.handler(album),
        icon: action.icon,
        variant: action.variant
    }));
};

const getAlbumItemCount = (album: Album) => {
    const count = album.images?.length || 0;
    return `${count} ${count === 1 ? 'item' : 'items'}`;
};
</script>
