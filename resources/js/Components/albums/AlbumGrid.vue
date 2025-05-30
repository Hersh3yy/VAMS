<template>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <AlbumItem
            v-for="item in items"
            :key="item.id"
            :item="item"
            :is-dragging="isDragging"
            :is-drag-over="isDragOver"
            :dragged-item="draggedItem"
            @click="handleItemClick"
            @delete="handleItemDelete"
            @dragstart="handleDragStart"
            @dragend="handleDragEnd"
            @dragenter="handleDragEnter"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
        />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AlbumItem from './AlbumItem.vue';
import type { AlbumImage, AlbumVideo } from '@/types/album';

const props = defineProps<{
    items: (AlbumImage | AlbumVideo)[];
}>();

const emit = defineEmits<{
    (e: 'item-click', item: AlbumImage | AlbumVideo): void;
    (e: 'item-delete', item: AlbumImage | AlbumVideo): void;
    (e: 'reorder', fromId: number, toId: number): void;
}>();

const isDragging = ref(false);
const isDragOver = ref(false);
const draggedItem = ref<(AlbumImage | AlbumVideo) | null>(null);

const handleItemClick = (item: AlbumImage | AlbumVideo) => {
    emit('item-click', item);
};

const handleItemDelete = (item: AlbumImage | AlbumVideo) => {
    emit('item-delete', item);
};

const handleDragStart = (event: DragEvent, item: AlbumImage | AlbumVideo) => {
    isDragging.value = true;
    draggedItem.value = item;
};

const handleDragEnd = () => {
    isDragging.value = false;
    isDragOver.value = false;
    draggedItem.value = null;
};

const handleDragEnter = (event: DragEvent, item: AlbumImage | AlbumVideo) => {
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        isDragOver.value = true;
    }
};

const handleDragLeave = () => {
    isDragOver.value = false;
};

const handleDrop = (event: DragEvent, item: AlbumImage | AlbumVideo) => {
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        emit('reorder', draggedItem.value.id, item.id);
    }
    isDragOver.value = false;
};
</script> 