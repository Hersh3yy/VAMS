<template>
    <div
        class="grid gap-4"
        :class="{
            'grid-cols-1': settings.grid_columns === 1,
            'grid-cols-2': settings.grid_columns === 2,
            'grid-cols-3': settings.grid_columns === 3,
            'grid-cols-4': settings.grid_columns === 4
        }"
        :style="{
            gap: `${settings.gap}px`,
            padding: `${settings.padding}px`
        }"
    >
        <!-- Column containers with add buttons -->
        <div
            v-for="columnIndex in settings.grid_columns"
            :key="columnIndex"
            class="space-y-4"
            :class="{
                'min-h-[200px] rounded-lg border-2 border-dashed border-gray-300 p-4':
                    columnItems[columnIndex - 1].length === 0
            }"
        >
            <!-- Items in this column -->
            <MosaicItemComponent
                v-for="item in columnItems[columnIndex - 1]"
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

            <!-- Add Item Button for this column -->
            <button
                @click="handleAddItem(columnIndex - 1)"
                class="w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center text-gray-500 transition-colors hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600"
                :class="{
                    'border-blue-400 bg-blue-50 text-blue-600':
                        columnItems[columnIndex - 1].length === 0
                }"
            >
                <svg
                    class="mx-auto mb-2 h-8 w-8"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    />
                </svg>
                <span class="text-sm font-medium">
                    {{ columnItems[columnIndex - 1].length === 0 ? 'Add First Item' : 'Add Item' }}
                </span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { MosaicDisplaySettings, MosaicItem } from '@/types/mosaic';
import { computed, ref } from 'vue';
import MosaicItemComponent from './MosaicItem.vue';

const props = defineProps<{
    items: MosaicItem[];
    settings: MosaicDisplaySettings;
}>();

const emit = defineEmits<{
    (e: 'item-click', item: MosaicItem): void;
    (e: 'item-delete', item: MosaicItem): void;
    (e: 'reorder', fromId: string, toId: string): void;
    (e: 'add-item', columnIndex: number): void;
}>();

const isDragging = ref(false);
const isDragOver = ref(false);
const draggedItem = ref<MosaicItem | null>(null);

// Organize items by column
const columnItems = computed(() => {
    const columns: MosaicItem[][] = Array.from({ length: props.settings.grid_columns }, () => []);

    props.items.forEach(item => {
        const columnIndex = item.column_index || 0;
        if (columnIndex < columns.length) {
            columns[columnIndex].push(item);
        }
    });

    // Sort items within each column by order
    columns.forEach(column => {
        column.sort((a, b) => (a.order || 0) - (b.order || 0));
    });

    return columns;
});

const handleItemClick = (item: MosaicItem) => {
    emit('item-click', item);
};

const handleItemDelete = (item: MosaicItem) => {
    emit('item-delete', item);
};

const handleAddItem = (columnIndex: number) => {
    emit('add-item', columnIndex);
};

const handleDragStart = (event: DragEvent, item: MosaicItem) => {
    isDragging.value = true;
    draggedItem.value = item;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', item.id);
    }
};

const handleDragEnd = () => {
    isDragging.value = false;
    isDragOver.value = false;
    draggedItem.value = null;
};

const handleDragEnter = (event: DragEvent, item: MosaicItem) => {
    event.preventDefault();
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        isDragOver.value = true;
    }
};

const handleDragLeave = () => {
    isDragOver.value = false;
};

const handleDrop = (event: DragEvent, item: MosaicItem) => {
    event.preventDefault();
    if (draggedItem.value && draggedItem.value.id !== item.id) {
        emit('reorder', draggedItem.value.id, item.id);
    }
    isDragOver.value = false;
};
</script>
