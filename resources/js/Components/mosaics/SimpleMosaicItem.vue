<template>
    <div
        class="relative bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow cursor-pointer group"
        @click="$emit('click', item)"
    >
        <!-- Item Content -->
        <div class="aspect-video bg-gray-100">
            <!-- Album Item -->
            <template v-if="item.type === 'album' && item.properties?.album">
                <img 
                    :src="item.properties.album.cover_image_path || '/placeholder.jpg'"
                    :alt="item.properties.album.title || 'Album'"
                    class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <h3 class="text-white text-sm font-medium text-center px-2">
                        {{ item.properties.album.title }}
                    </h3>
                </div>
            </template>

            <!-- Media Item -->
            <template v-else-if="item.type === 'media' && item.properties?.media_url">
                <img 
                    :src="item.properties.media_url" 
                    :alt="item.properties.title || 'Media'"
                    class="w-full h-full object-cover"
                />
            </template>

            <!-- Color Item -->
            <template v-else-if="item.type === 'color'">
                <div 
                    class="w-full h-full flex items-center justify-center"
                    :style="{ backgroundColor: item.properties?.color || '#ffffff' }"
                >
                    <span 
                        v-if="item.properties?.text" 
                        class="text-sm font-medium text-center px-2"
                        :style="{ color: getContrastColor(item.properties?.color || '#ffffff') }"
                    >
                        {{ item.properties.text }}
                    </span>
                </div>
            </template>

            <!-- Text Item -->
            <template v-else-if="item.type === 'text'">
                <div class="w-full h-full flex items-center justify-center bg-gray-50 p-4">
                    <p class="text-sm text-gray-800 text-center">
                        {{ item.properties?.text || 'Text content' }}
                    </p>
                </div>
            </template>

            <!-- Placeholder for empty items -->
            <template v-else>
                <div class="w-full h-full flex items-center justify-center bg-gray-100">
                    <div class="text-center text-gray-400">
                        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-xs">Empty Item</p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Item Controls -->
        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button 
                @click.stop="$emit('delete', item)"
                class="bg-red-600 text-white rounded-full p-1 hover:bg-red-700 transition-colors"
                title="Delete item"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>

        <!-- Type Badge -->
        <div class="absolute bottom-2 left-2">
            <span class="bg-black bg-opacity-60 text-white text-xs px-2 py-1 rounded">
                {{ item.type }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { MosaicItem } from '@/types/mosaic';

const props = defineProps<{
    item: MosaicItem;
}>();

const emit = defineEmits<{
    (e: 'click', item: MosaicItem): void;
    (e: 'delete', item: MosaicItem): void;
}>();

// Helper function to get contrasting text color
const getContrastColor = (hexColor: string): string => {
    // Remove # if present
    const color = hexColor.replace('#', '');
    
    // Convert to RGB
    const r = parseInt(color.substr(0, 2), 16);
    const g = parseInt(color.substr(2, 2), 16);
    const b = parseInt(color.substr(4, 2), 16);
    
    // Calculate luminance
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    
    return luminance > 0.5 ? '#000000' : '#ffffff';
};
</script>

<style scoped>
.group {
    transition: all 0.2s ease-in-out;
}
</style> 