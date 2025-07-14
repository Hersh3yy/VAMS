<template>
    <IndexLayout
        title="Mosaics"
        :items="mosaics"
        :create-route="route('mosaics.create')"
        create-button-text="Create New Mosaic"
        empty-state-title="No Mosaics Yet"
        empty-state-message="Create your first mosaic layout to get started."
        empty-state-button-text="Create First Mosaic"
        :get-item-route="(mosaic) => route('mosaics.show', mosaic.id)"
        :get-item-actions="getMosaicActions"
    >
        <template #item-image="{ item }">
            <!-- Check if mosaic has a cover image -->
            <img 
                v-if="getMosaicCoverImage(item)"
                :src="getMosaicCoverImage(item)"
                :alt="item.title"
                class="w-full h-full object-cover"
            />
            <!-- Fallback to mosaic preview -->
            <div v-else class="absolute inset-0 grid grid-cols-2 gap-1 p-2">
                <div class="bg-gray-200 rounded"></div>
                <div class="bg-gray-300 rounded"></div>
                <div class="bg-gray-300 rounded"></div>
                <div class="bg-gray-200 rounded"></div>
            </div>
        </template>
        
        <template #item-footer="{ item }">
            <div class="mt-4 flex justify-between items-center">
                <span class="text-xs text-gray-500">
                    Created {{ new Date(item.created_at).toLocaleDateString() }}
                </span>
                <span class="text-xs text-gray-500">
                    {{ item.items?.length || 0 }} tiles
                </span>
            </div>
        </template>
    </IndexLayout>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import IndexLayout from '@/Components/shared/IndexLayout.vue';
import type { Mosaic } from '@/types/mosaic';

const props = defineProps<{
    mosaics: Mosaic[];
}>();

const deleteMosaic = (mosaic: Mosaic) => {
    if (confirm(`Are you sure you want to delete "${mosaic.title}"?`)) {
        router.delete(route('mosaics.destroy', mosaic.id));
    }
};

const getMosaicActions = (mosaic: Mosaic) => [
    {
        label: 'Edit Mosaic',
        handler: () => router.visit(route('mosaics.edit', mosaic.id)),
        icon: 'svg',
        class: 'p-2 bg-secondary text-black rounded-full hover:brightness-90'
    },
    {
        label: 'Delete Mosaic',
        handler: () => deleteMosaic(mosaic),
        icon: 'svg',
        class: 'btn-danger p-2 rounded-full'
    }
];

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