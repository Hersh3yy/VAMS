<template>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div 
            v-for="item in items" 
            :key="item.id"
            class="relative group"
            :class="{ 
                'opacity-30': isDragging && draggedItem?.id === item.id,
                'transform scale-102': isDragOver && dragTargetItem?.id === item.id
            }"
        >
            <AlbumItem
                :item="item"
                :is-dragging="isDragging"
                :is-drag-over="isDragOver && dragTargetItem?.id === item.id"
                :dragged-item="draggedItem"
                @click="handleItemClick"
                @delete="handleItemDelete"
                @dragstart="handleDragStart"
                @dragend="handleDragEnd"
                @dragenter="handleDragEnter"
                @dragleave="handleDragLeave"
                @drop="handleDrop"
                @dragover="handleDragOver"
            />
            
            <!-- Drag indicator -->
            <div 
                v-if="isDragOver && dragTargetItem?.id === item.id"
                class="absolute inset-0 border-2 border-blue-500 border-dashed rounded-lg bg-blue-50/30 transition-all duration-150"
            ></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AlbumItem from './AlbumItem.vue';
import type { AlbumImage } from '@/types/album';

const props = defineProps<{
    items: AlbumImage[];
}>();

const emit = defineEmits<{
    (e: 'item-click', item: AlbumImage): void;
    (e: 'item-delete', item: AlbumImage): void;
    (e: 'reorder', fromId: string, toId: string): void;
}>();

const isDragging = ref(false);
const isDragOver = ref(false);
const draggedItem = ref<AlbumImage | null>(null);
const dragTargetItem = ref<AlbumImage | null>(null);

const handleItemClick = (item: AlbumImage) => {
    if (!isDragging.value) {
        emit('item-click', item);
    }
};

const handleItemDelete = (item: AlbumImage) => {
    emit('item-delete', item);
};

const handleDragStart = (event: DragEvent, item: AlbumImage) => {
    if (!event.dataTransfer) return;
    
    isDragging.value = true;
    draggedItem.value = item;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', item.id);
};

const handleDragEnd = () => {
    isDragging.value = false;
    isDragOver.value = false;
    draggedItem.value = null;
    dragTargetItem.value = null;
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    event.dataTransfer!.dropEffect = 'move';
};

const handleDragEnter = (event: DragEvent, item: AlbumImage) => {
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        isDragOver.value = true;
        dragTargetItem.value = item;
        event.preventDefault();
    }
};

const handleDragLeave = (event: DragEvent) => {
    // Only hide indicator if we're leaving the entire drop zone
    const relatedTarget = event.relatedTarget as HTMLElement;
    const currentTarget = event.currentTarget as HTMLElement;
    
    if (!currentTarget.contains(relatedTarget)) {
        isDragOver.value = false;
        dragTargetItem.value = null;
    }
};

const handleDrop = (event: DragEvent, item: AlbumImage) => {
    event.preventDefault();
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        emit('reorder', draggedItem.value.id, item.id);
    }
    
    // Clean up drag state
    handleDragEnd();
};
</script>

<style scoped>
/* Smooth transitions for drag and drop effects */
.relative {
    transition: transform 150ms ease-out, opacity 150ms ease-out;
}

.scale-102 {
    transform: scale(1.02);
}

/* Reduce animation intensity on the drag indicator */
.border-dashed {
    animation: none;
}

/* Add subtle hover effects */
.group:hover .relative {
    transform: translateY(-1px);
}
</style> 