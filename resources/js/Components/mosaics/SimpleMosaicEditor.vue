<template>
    <div class="simple-mosaic-editor">
        <!-- Header with column controls -->
        <div class="mb-6 flex items-center justify-between rounded-lg bg-gray-50 p-4">
            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-gray-700">Columns:</label>
                <select
                    v-model="columnCount"
                    @change="updateColumns"
                    class="form-select rounded-md border-gray-300 shadow-sm focus:border-secondary focus:ring-secondary"
                >
                    <option value="1">1 Column</option>
                    <option value="2">2 Columns</option>
                    <option value="3">3 Columns</option>
                    <option value="4">4 Columns</option>
                    <option value="5">5 Columns</option>
                </select>
            </div>
        </div>

        <!-- Column Layout -->
        <div class="grid gap-6" :style="{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }">
            <div
                v-for="columnIndex in columnCount"
                :key="`column-${columnIndex}`"
                class="min-h-[400px] rounded-lg border-2 border-dashed border-gray-300 p-4"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Column {{ columnIndex }}</h3>
                    <button @click="addItem(columnIndex - 1)" class="btn-secondary text-sm">
                        + Add Item
                    </button>
                </div>

                <!-- Items in this column -->
                <Draggable
                    v-model="columnItems[columnIndex - 1]"
                    class="min-h-[200px] space-y-4"
                    :transition="200"
                    group="mosaic-items"
                    :empty-insert-threshold="50"
                    ghost-class="ghost-item"
                    chosen-class="chosen-item"
                >
                    <template v-slot:item="{ item }">
                        <div class="group relative">
                            <SimpleMosaicItem
                                :item="item"
                                @click="editItem(item)"
                                @delete="deleteItem(item)"
                            />
                        </div>
                    </template>
                </Draggable>

                <!-- Empty column placeholder -->
                <div
                    v-if="columnItems[columnIndex - 1]?.length === 0"
                    class="flex h-32 items-center justify-center text-gray-400"
                >
                    <div class="text-center">
                        <svg
                            class="mx-auto mb-2 h-12 w-12"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <p>Drop items here or click "Add Item"</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Item Editor Modal -->
        <SimpleMosaicItemEditor
            :show="showItemEditor"
            :item="editingItem"
            :albums="albums"
            :mosaicId="mosaicId"
            @close="closeItemEditor"
            @save="saveItem"
            @delete="deleteItem"
            @update="handleItemUpdate"
        />
    </div>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
// @ts-ignore
import type { Album, Mosaic, MosaicItem } from '@/types/mosaic';
import Draggable from 'vue3-draggable';
import SimpleMosaicItem from './SimpleMosaicItem.vue';
import SimpleMosaicItemEditor from './SimpleMosaicItemEditor.vue';

const props = defineProps<{
    mosaic: Mosaic;
    albums: Album[];
    mosaicId?: string;
}>();

const emit = defineEmits<{
    (e: 'update', mosaic: Mosaic): void;
    (e: 'save'): void;
}>();

// State
const columnCount = ref(props.mosaic.columns || 3);
const items = ref<MosaicItem[]>(props.mosaic.items || []);
const showItemEditor = ref(false);
const editingItem = ref<MosaicItem | null>(null);

// Reactive column arrays for drag and drop
const columnItems = ref<MosaicItem[][]>([]);

// Initialize column arrays from items
const initializeColumns = () => {
    columnItems.value = Array.from({ length: columnCount.value }, (_, colIndex) =>
        items.value.filter(item => item.column_index === colIndex).sort((a, b) => a.order - b.order)
    );
};

// Initialize columns when component mounts or data changes
watch([items, columnCount], initializeColumns, { immediate: true, deep: true });

// Sync column items back to main items array when dragged
watch(
    columnItems,
    newColumnItems => {
        const newItems: MosaicItem[] = [];

        newColumnItems.forEach((columnItemList, columnIndex) => {
            columnItemList.forEach((item, order) => {
                newItems.push({
                    ...item,
                    column_index: columnIndex,
                    order
                });
            });
        });

        // Only update if there's actually a change to prevent infinite loops
        if (JSON.stringify(newItems) !== JSON.stringify(items.value)) {
            items.value = newItems;
            emitUpdate();
        }
    },
    { deep: true }
);

// Watch for external changes from parent
watch(
    () => props.mosaic,
    newMosaic => {
        if (newMosaic.columns !== columnCount.value) {
            columnCount.value = newMosaic.columns || 3;
        }
        if (JSON.stringify(newMosaic.items) !== JSON.stringify(items.value)) {
            items.value = [...(newMosaic.items || [])];
        }
    },
    { deep: true, immediate: true }
);

// Methods
const updateColumns = () => {
    initializeColumns();
    emitUpdate();
};

const addItem = (columnIndex: number) => {
    const newItem: MosaicItem = {
        id: '',
        type: 'album',
        column_index: columnIndex,
        order: columnItems.value[columnIndex]?.length || 0,
        properties: {}
    };

    editingItem.value = newItem;
    showItemEditor.value = true;
};

const editItem = (item: MosaicItem) => {
    editingItem.value = { ...item };
    showItemEditor.value = true;
};

const deleteItem = (item: MosaicItem) => {
    if (confirm('Are you sure you want to delete this item?')) {
        items.value = items.value.filter(i => i.id !== item.id);
        emitUpdate();
    }
};

const saveItem = (item: MosaicItem) => {
    // Generate temporary ID for new items
    if (!item.id || item.id === '') {
        item.id = `temp_${Date.now()}_${Math.random()}`;
    }

    const existingIndex = items.value.findIndex(i => i.id === item.id);

    if (existingIndex !== -1) {
        items.value[existingIndex] = { ...item };
    } else {
        items.value.push({ ...item });
    }

    closeItemEditor();
    emitUpdate();

    // Force UI update
    nextTick(() => {
        initializeColumns();
    });
};

const closeItemEditor = () => {
    showItemEditor.value = false;
    editingItem.value = null;
};

const handleItemUpdate = () => {
    // Force UI update and emit changes
    nextTick(() => {
        initializeColumns();
        emitUpdate();
    });
};

const emitUpdate = () => {
    emit('update', {
        ...props.mosaic,
        columns: columnCount.value,
        items: items.value
    });
};
</script>

<style scoped>
.ghost-item {
    @apply border-2 border-dashed border-blue-300 bg-blue-100 opacity-50;
}

.chosen-item {
    @apply scale-105 transform ring-2 ring-blue-500;
}
</style>
