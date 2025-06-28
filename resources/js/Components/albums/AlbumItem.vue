<template>
    <div
        class="aspect-square relative bg-gray-100 rounded-lg overflow-hidden cursor-pointer group"
        @click="$emit('click', item)"
    >
        <!-- Video badge -->
        <div v-if="isVideo" class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1 z-10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        
        <!-- Delete button -->
        <button 
            @click.stop="handleDelete" 
            class="absolute top-2 left-2 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity z-10 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
        
        <img 
            :src="imageSrc" 
            :alt="item.title || 'Album item'"
            class="object-cover w-full h-full transition-transform duration-200 group-hover:scale-105"
        >
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { AlbumImage } from '@/types/album';

const props = defineProps<{
    item: AlbumImage;
}>();

const emit = defineEmits<{
    (e: 'click', item: AlbumImage): void;
    (e: 'delete', item: AlbumImage): void;
}>();

const isVideo = computed(() => {
    const item = props.item as AlbumImage;
    
    // Handle both string and object properties
    if (item.properties) {
        const properties = typeof item.properties === 'string' 
            ? JSON.parse(item.properties) 
            : item.properties;
        
        if (properties?.type === 'video') {
            return true;
        }
    }
    
    // Fallback check based on path
    return item.path?.includes('youtube.com') || 
           item.path?.includes('youtu.be') || 
           item.path?.includes('vimeo.com');
});

const imageSrc = computed(() => {
    const item = props.item as AlbumImage;
    if (isVideo.value) {
        // For videos, try to use the thumbnail URL first
        if (item.properties) {
            const properties = typeof item.properties === 'string' 
                ? JSON.parse(item.properties) 
                : item.properties;
            
            if (properties?.thumbnail_url) {
                return properties.thumbnail_url;
            }
        }
        // Fallback to video placeholder if no thumbnail
        return '/images/video-placeholder.svg';
    }
    return item.path;
});

const handleDelete = (event: MouseEvent) => {
    event.preventDefault();
    event.stopPropagation();
    emit('delete', props.item);
};
</script>

<style scoped>
.group {
    transition: all 0.2s ease-in-out;
}

.group:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style> 