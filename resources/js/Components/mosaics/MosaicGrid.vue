<template>
    <div 
        class="grid gap-4"
        :class="{
            'grid-cols-1': settings.grid_columns === 1,
            'grid-cols-2': settings.grid_columns === 2,
            'grid-cols-3': settings.grid_columns === 3,
            'grid-cols-4': settings.grid_columns === 4,
        }"
        :style="{
            gap: `${settings.gap}px`,
            padding: `${settings.padding}px`
        }"
    >
        <MosaicItemComponent
            v-for="item in items"
            :key="item.id"
            :item="item"
            :is-dragging="isDragging"
            :is-drag-over="isDragOver"
            :dragged-item="draggedItem"
            :settings="settings"
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
import MosaicItemComponent from './MosaicItem.vue';
import type { MosaicItem, MosaicDisplaySettings } from '@/types/mosaic';

const props = defineProps<{
    items: MosaicItem[];
    settings: MosaicDisplaySettings;
}>();

const emit = defineEmits<{
    (e: 'item-click', item: MosaicItem): void;
    (e: 'item-delete', item: MosaicItem): void;
    (e: 'reorder', fromId: string, toId: string): void;
}>();

const isDragging = ref(false);
const isDragOver = ref(false);
const draggedItem = ref<MosaicItem | null>(null);

const handleItemClick = (item: MosaicItem) => {
    emit('item-click', item);
};

const handleItemDelete = (item: MosaicItem) => {
    emit('item-delete', item);
};

const handleDragStart = (event: DragEvent, item: MosaicItem) => {
    isDragging.value = true;
    draggedItem.value = item;
};

const handleDragEnd = () => {
    isDragging.value = false;
    isDragOver.value = false;
    draggedItem.value = null;
};

const handleDragEnter = (event: DragEvent, item: MosaicItem) => {
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        isDragOver.value = true;
    }
};

const handleDragLeave = () => {
    isDragOver.value = false;
};

const handleDrop = (event: DragEvent, item: MosaicItem) => {
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        emit('reorder', draggedItem.value.id, item.id);
    }
    isDragOver.value = false;
};
</script> 