<template>
    <div v-if="show && video" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg overflow-hidden shadow-xl transform w-full max-w-4xl">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-medium">{{ video.title || 'Video' }}</h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <div class="aspect-video">
                    <iframe
                        v-if="videoEmbedUrl"
                        :src="videoEmbedUrl"
                        class="w-full h-full"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
                <div v-if="video.caption" class="mt-4 p-4 bg-gray-100 rounded">
                    <p>{{ video.caption }}</p>
                </div>
            </div>
            <div class="p-4 border-t flex justify-end">
                <button @click="$emit('delete', video)" class="text-red-600 hover:text-red-800 mr-4">
                    Delete Video
                </button>
                <button @click="$emit('close')" class="btn-primary">
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { AlbumImage } from '@/types/album';

const props = defineProps<{
    show: boolean;
    video: AlbumImage | null;
}>();

const emit = defineEmits<{
    close: [];
    delete: [video: AlbumImage];
}>();

const videoEmbedUrl = computed(() => {
    if (!props.video) return null;
    if (props.video.properties?.type === 'video') {
        const url = props.video.properties.video_url || props.video.path;
        // Convert to embed URL for YouTube/Vimeo
        if (url.includes('youtube.com/watch')) {
            const videoId = url.split('v=')[1]?.split('&')[0];
            return `https://www.youtube.com/embed/${videoId}`;
        } else if (url.includes('youtu.be/')) {
            const videoId = url.split('youtu.be/')[1]?.split('?')[0];
            return `https://www.youtube.com/embed/${videoId}`;
        } else if (url.includes('vimeo.com/')) {
            const videoId = url.split('vimeo.com/')[1]?.split('?')[0];
            return `https://player.vimeo.com/video/${videoId}`;
        }
    }
    return null;
});
</script> 