<template>
    <ContentGrid
        :items="mosaics"
        :create-route="createRoute"
        :get-item-route="getItemRoute"
        empty-title="No Mosaics Yet"
        empty-message="Create your first mosaic layout to get started."
        empty-button-text="Create First Mosaic"
    >
        <template #image="{ item }">
            <!-- Check if mosaic has a cover image -->
            <img
                v-if="getMosaicCoverImage(item)"
                :src="getMosaicCoverImage(item)"
                :alt="item.title"
                class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
            />
            <!-- Fallback to mosaic preview -->
            <div v-else class="h-full w-full">
                <div class="grid h-full grid-cols-2 gap-1 p-2">
                    <div class="rounded bg-gray-200" />
                    <div class="rounded bg-gray-300" />
                    <div class="rounded bg-gray-300" />
                    <div class="rounded bg-gray-200" />
                </div>
            </div>
        </template>

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
            <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
                <span>Created {{ new Date(item.created_at).toLocaleDateString() }}</span>
                <span>{{ item.items?.length || 0 }} tiles</span>
            </div>
        </template>
    </ContentGrid>
</template>

<script setup lang="ts">
import ActionButtons from '@/Components/molecules/ActionButtons.vue';
import ContentGrid from '@/Components/organisms/ContentGrid.vue';
import type { Mosaic } from '@/types/mosaic';

interface MosaicAction {
    label: string;
    handler: (mosaic: Mosaic) => void;
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
    mosaics: Mosaic[];
    createRoute: string;
    getItemRoute: (mosaic: Mosaic) => string;
    actions?: (mosaic: Mosaic) => MosaicAction[];
}

const props = defineProps<Props>();

const getSimpleActions = (mosaic: Mosaic) => {
    if (!props.actions) return [];

    return props.actions(mosaic).map(action => ({
        label: action.label,
        handler: () => action.handler(mosaic),
        icon: action.icon,
        variant: action.variant
    }));
};

const getMosaicCoverImage = (mosaic: Mosaic): string | undefined => {
    // Find the first image in the mosaic items
    for (const item of mosaic.items) {
        if (item.type === 'album' && item.properties?.selected_image?.path) {
            return item.properties.selected_image.path;
        }
        if (item.type === 'album' && item.properties?.album?.cover_image_path) {
            return item.properties.album.cover_image_path;
        }
        if (item.type === 'media' && item.properties?.media?.path) {
            return item.properties.media.path;
        }
        if (item.type === 'media' && item.properties?.media_url) {
            return item.properties.media_url;
        }
    }
    return undefined;
};
</script>
