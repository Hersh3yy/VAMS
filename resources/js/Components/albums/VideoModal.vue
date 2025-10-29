<template>
    <BaseModal v-if="show && video" size="4xl" closeable @close="$emit('close')">
        <template #header>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ video.title || 'Video' }}
            </h3>
        </template>

        <template #body>
            <div class="aspect-video">
                <iframe
                    v-if="videoEmbedUrl"
                    :src="videoEmbedUrl"
                    class="h-full w-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                />
            </div>
            <div v-if="video.caption" class="mt-4 rounded bg-gray-100 dark:bg-gray-700 p-4">
                <p class="text-gray-900 dark:text-gray-100">{{ video.caption }}</p>
            </div>
        </template>

        <template #footer>
            <BaseButton variant="danger" @click="$emit('delete', video)">
                Delete Video
            </BaseButton>
            <BaseButton variant="secondary" @click="$emit('close')">
                Close
            </BaseButton>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import type { AlbumImage } from '@/types/album';
import { computed } from 'vue';
import BaseModal from '@/Components/Base/Modal.vue';
import BaseButton from '@/Components/Base/Button.vue';

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
