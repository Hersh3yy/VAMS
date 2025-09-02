<template>
    <div
        class="group relative aspect-square cursor-pointer overflow-hidden rounded-lg bg-gray-100"
        @click="$emit('click', item)"
    >
        <!-- Video badge -->
        <Badge v-if="isVideo" variant="danger" size="sm" class="absolute right-2 top-2 z-10">
            <Icon name="play-circle" size="sm" class="text-white" />
        </Badge>

        <!-- Delete button -->
        <Button
            variant="danger"
            size="sm"
            icon-only
            class="absolute left-2 top-2 z-10 opacity-0 transition-opacity group-hover:opacity-100"
            @click.stop="handleDelete"
        >
            <Icon name="trash" size="sm" />
        </Button>

        <img
            :src="imageSrc"
            :alt="item.title || 'Album item'"
            class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
        >
    </div>
</template>

<script setup lang="ts">
import Badge from '@/Components/Base/Badge.vue';
import Button from '@/Components/Base/Button.vue';
import Icon from '@/Components/Base/Icon.vue';
import type { AlbumImage } from '@/types/album';
import { computed } from 'vue';

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
        const properties =
            typeof item.properties === 'string' ? JSON.parse(item.properties) : item.properties;

        if (properties?.type === 'video') {
            return true;
        }
    }

    // Fallback check based on path
    return (
        item.path?.includes('youtube.com') ||
        item.path?.includes('youtu.be') ||
        item.path?.includes('vimeo.com')
    );
});

const imageSrc = computed(() => {
    const item = props.item as AlbumImage;
    if (isVideo.value) {
        // For videos, try to use the thumbnail URL first
        if (item.properties) {
            const properties =
                typeof item.properties === 'string' ? JSON.parse(item.properties) : item.properties;

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
