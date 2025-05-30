<template>
    <div
        class="aspect-square relative bg-gray-100 rounded-lg overflow-hidden cursor-move group"
        :class="{
            'opacity-50 ring-4 ring-blue-500': isDragging && draggedItem?.id === item.id,
            'ring-4 ring-green-500': isDragOver && draggedItem?.id !== item.id
        }"
        draggable="true"
        @click="$emit('click', item)"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
        @dragover.prevent
        @dragenter.prevent="handleDragEnter"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDrop"
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
            @click.stop="$emit('delete', item)" 
            class="absolute top-2 left-2 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity z-10"
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
import type { AlbumImage, AlbumVideo } from '@/types/album';

const props = defineProps<{
    item: AlbumImage | AlbumVideo;
    isDragging: boolean;
    isDragOver: boolean;
    draggedItem: (AlbumImage | AlbumVideo) | null;
}>();

const emit = defineEmits<{
    (e: 'click', item: AlbumImage | AlbumVideo): void;
    (e: 'delete', item: AlbumImage | AlbumVideo): void;
    (e: 'dragstart', event: DragEvent, item: AlbumImage | AlbumVideo): void;
    (e: 'dragend'): void;
    (e: 'dragenter', event: DragEvent, item: AlbumImage | AlbumVideo): void;
    (e: 'dragleave'): void;
    (e: 'drop', event: DragEvent, item: AlbumImage | AlbumVideo): void;
}>();

const isVideo = computed(() => {
    return 'embed_url' in props.item;
});

const imageSrc = computed(() => {
    if (isVideo.value) {
        // For videos, we might want to show a thumbnail
        return (props.item as AlbumVideo).path;
    }
    return (props.item as AlbumImage).path;
});

const handleDragStart = (event: DragEvent) => {
    emit('dragstart', event, props.item);
};

const handleDragEnd = () => {
    emit('dragend');
};

const handleDragEnter = (event: DragEvent) => {
    emit('dragenter', event, props.item);
};

const handleDragLeave = () => {
    emit('dragleave');
};

const handleDrop = (event: DragEvent) => {
    emit('drop', event, props.item);
};
</script> 