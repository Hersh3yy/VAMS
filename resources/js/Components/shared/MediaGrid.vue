<template>
    <div
        class="grid gap-4"
        :class="{
            'grid-cols-1': settings.grid_columns === 1,
            'grid-cols-2': settings.grid_columns === 2,
            'grid-cols-3': settings.grid_columns === 3,
            'grid-cols-4': settings.grid_columns === 4,
        }"
    >
        <slot
            v-for="item in items"
            :key="item.id"
            :item="item"
            :is-dragging="isDragging"
            :is-drag-over="isDragOver"
            :dragged-item="draggedItem"
            @click="$emit('item-click', item)"
            @delete="$emit('item-delete', item)"
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

interface DisplaySettings {
    grid_columns: number;
    gap: number;
    padding: number;
    show_titles: boolean;
    show_captions: boolean;
}

const props = defineProps<{
    items: any[];
    settings: DisplaySettings;
}>();

const emit = defineEmits<{
    (e: 'item-click', item: any): void;
    (e: 'item-delete', item: any): void;
    (e: 'reorder', fromId: number, toId: number): void;
}>();

const isDragging = ref(false);
const isDragOver = ref(false);
const draggedItem = ref<any | null>(null);

const handleDragStart = (event: DragEvent, item: any) => {
    isDragging.value = true;
    draggedItem.value = item;
};

const handleDragEnd = () => {
    isDragging.value = false;
    isDragOver.value = false;
    draggedItem.value = null;
};

const handleDragEnter = (event: DragEvent, item: any) => {
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        isDragOver.value = true;
    }
};

const handleDragLeave = () => {
    isDragOver.value = false;
};

const handleDrop = (event: DragEvent, item: any) => {
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        emit('reorder', draggedItem.value.id, item.id);
    }
    isDragOver.value = false;
};
</script>
