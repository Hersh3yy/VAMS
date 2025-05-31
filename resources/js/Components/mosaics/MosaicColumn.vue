<template>
    <div class="flex flex-col space-y-4" :class="columnClass">
        <div 
            v-for="item in items" 
            :key="item.id"
            class="relative group transition-all duration-200"
            :class="{ 'opacity-50': isDragging && draggedItem?.id === item.id }"
            draggable="true"
            @dragstart="handleDragStart($event, item)"
            @dragend="handleDragEnd"
            @dragover.prevent
            @dragenter.prevent="handleDragEnter($event, item)"
            @dragleave="handleDragLeave"
            @drop.prevent="handleDrop($event, item)"
        >
            <MosaicItem
                :item="item"
                :settings="settings"
                :is-dragging="isDragging"
                :is-drag-over="isDragOver"
                :dragged-item="draggedItem"
                @click="handleItemClick"
                @delete="handleItemDelete"
                @edit="handleItemEdit"
            />
            
            <!-- Drag indicator -->
            <div 
                v-if="isDragOver && dragTargetItem?.id === item.id"
                class="absolute inset-0 border-2 border-blue-500 border-dashed rounded-lg bg-blue-50 bg-opacity-50"
            ></div>
        </div>
        
        <!-- Add Item Button -->
        <button
            @click="handleAddItem"
            class="w-full p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-all duration-200 group"
        >
            <div class="flex flex-col items-center justify-center text-gray-500 group-hover:text-blue-600">
                <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span class="text-sm font-medium">Add Item</span>
            </div>
        </button>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, inject } from 'vue';
import MosaicItem from './MosaicItem.vue';
import type { MosaicItem as MosaicItemType, MosaicDisplaySettings } from '@/types/mosaic';

const props = defineProps<{
    items: MosaicItemType[];
    columnIndex: number;
    settings: MosaicDisplaySettings;
}>();

const emit = defineEmits<{
    (e: 'item-click', item: MosaicItemType): void;
    (e: 'item-delete', item: MosaicItemType): void;
    (e: 'item-edit', item: MosaicItemType): void;
    (e: 'item-reorder', fromId: string, toId: string): void;
    (e: 'add-item', columnIndex: number): void;
}>();

const showError = inject('showError', (message: string) => console.error(message));

const isDragging = ref(false);
const isDragOver = ref(false);
const draggedItem = ref<MosaicItemType | null>(null);
const dragTargetItem = ref<MosaicItemType | null>(null);

const columnClass = computed(() => {
    return `mosaic-column-${props.columnIndex.toString()}`;
});

const handleItemClick = (item: MosaicItemType) => {
    try {
        emit('item-click', item);
    } catch (error) {
        showError('Failed to open item');
    }
};

const handleItemDelete = (item: MosaicItemType) => {
    try {
        emit('item-delete', item);
    } catch (error) {
        showError('Failed to delete item');
    }
};

const handleItemEdit = (item: MosaicItemType) => {
    try {
        emit('item-edit', item);
    } catch (error) {
        showError('Failed to edit item');
    }
};

const handleAddItem = () => {
    try {
        emit('add-item', props.columnIndex);
    } catch (error) {
        showError('Failed to add item');
    }
};

const handleDragStart = (event: DragEvent, item: MosaicItemType) => {
    if (!event.dataTransfer) return;
    
    isDragging.value = true;
    draggedItem.value = item;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', item.id.toString());
};

const handleDragEnd = () => {
    isDragging.value = false;
    isDragOver.value = false;
    draggedItem.value = null;
    dragTargetItem.value = null;
};

const handleDragEnter = (event: DragEvent, item: MosaicItemType) => {
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        isDragOver.value = true;
        dragTargetItem.value = item;
    }
};

const handleDragLeave = () => {
    isDragOver.value = false;
    dragTargetItem.value = null;
};

const handleDrop = (event: DragEvent, item: MosaicItemType) => {
    event.preventDefault();
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        try {
            emit('item-reorder', draggedItem.value.id.toString(), item.id.toString());
        } catch (error) {
            showError('Failed to reorder items');
        }
    }
    isDragOver.value = false;
    dragTargetItem.value = null;
};
</script>

<style scoped>
.mosaic-column {
    min-height: 200px;
}
</style> 