<template>
    <div class="simple-mosaic-editor">
        <!-- Header with column controls -->
        <div class="flex justify-between items-center mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-gray-700">Columns:</label>
                <select 
                    v-model="columnCount" 
                    @change="updateColumns"
                    class="form-select rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
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
        <div 
            class="grid gap-6"
            :style="{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }"
        >
            <div 
                v-for="columnIndex in columnCount" 
                :key="`column-${columnIndex}-${reactivityKey}`"
                class="min-h-[400px] border-2 border-dashed border-gray-300 rounded-lg p-4"
            >
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Column {{ columnIndex }}</h3>
                    <button 
                        @click="addItem(columnIndex - 1)"
                        class="btn-secondary text-sm"
                    >
                        + Add Item
                    </button>
                </div>

                <!-- Items in this column -->
                <Draggable 
                    v-model="columnItems[columnIndex - 1]" 
                    :key="`draggable-${columnIndex}-${reactivityKey}`"
                    class="space-y-4 min-h-[200px]"
                    :transition="200"
                    group="mosaic-items"
                    :empty-insert-threshold="50"
                    ghost-class="ghost-item"
                    chosen-class="chosen-item"
                >
                    <template v-slot:item="{ item }">
                        <div class="relative group">
                            <SimpleMosaicItem
                                :item="item"
                                @click="editItem(item)"
                                @delete="deleteItem(item)"
                            />
                        </div>
                    </template>
                </Draggable>

                <!-- Add item placeholder -->
                <div 
                    v-if="itemsInColumn(columnIndex - 1).length === 0"
                    class="flex items-center justify-center h-32 text-gray-400"
                >
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
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
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
// @ts-ignore
import Draggable from 'vue3-draggable';
import SimpleMosaicItem from './SimpleMosaicItem.vue';
import SimpleMosaicItemEditor from './SimpleMosaicItemEditor.vue';
import type { Mosaic, MosaicItem, Album } from '@/types/mosaic';

const props = defineProps<{
    mosaic: Mosaic;
    albums: Album[];
    mosaicId?: string;
}>();

const emit = defineEmits<{
    (e: 'update', mosaic: Mosaic): void;
    (e: 'save'): void;
}>();

const columnCount = ref(props.mosaic.columns || 3);
const items = ref<MosaicItem[]>(props.mosaic.items || []);
const showItemEditor = ref(false);
const editingItem = ref<MosaicItem | null>(null);
const hasChanges = ref(false);

// Add a reactivity key to force re-renders when needed
const reactivityKey = ref(0);

// Create reactive column arrays
const columnItems = ref<MosaicItem[][]>([]);

// Initialize column arrays
const initializeColumns = () => {
    columnItems.value = Array.from({ length: columnCount.value }, (_, colIndex) => 
        items.value.filter(item => item.column_index === colIndex)
            .sort((a, b) => a.order - b.order)
    );
    
    // Force re-render by incrementing reactivity key
    reactivityKey.value++;
};

// Watch items and columns to reinitialize
watch([items, columnCount], () => {
    initializeColumns();
}, { immediate: true, deep: true });

const itemsInColumn = (columnIndex: number) => {
    return items.value.filter(item => item.column_index === columnIndex)
        .sort((a, b) => a.order - b.order);
};

const getColumnItemsReactive = (columnIndex: number) => {
    if (!columnItems.value[columnIndex]) {
        columnItems.value[columnIndex] = [];
    }
    return columnItems.value[columnIndex];
};

const updateColumns = () => {
    // Reinitialize column arrays when column count changes
    initializeColumns();
    hasChanges.value = true;
    emitUpdate();
};

const addItem = (columnIndex: number) => {
    const newItem: MosaicItem = {
        id: '', // No ID needed for creation - backend will generate
        type: 'album', // MVP: Default to album type for simplified workflow
        column_index: columnIndex,
        order: itemsInColumn(columnIndex).length,
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
        hasChanges.value = true;
        emitUpdate();
    }
};

const saveItem = (item: MosaicItem) => {
    // For new items (empty ID), generate a temporary unique ID for client-side tracking
    if (!item.id || item.id === '') {
        item.id = `temp_${Date.now()}_${Math.random()}`;
    }
    
    const existingIndex = items.value.findIndex(i => i.id === item.id);
    
    if (existingIndex !== -1) {
        items.value[existingIndex] = { ...item };
    } else {
        items.value.push({ ...item });
    }
    
    // Force re-initialization of columns
    initializeColumns();
    
    hasChanges.value = true;
    closeItemEditor();
    emitUpdate();
    
    // Force reactivity update
    nextTick(() => {
        // Trigger component re-render by updating a reactive property
        columnItems.value = [...columnItems.value];
    });
};

const closeItemEditor = () => {
    showItemEditor.value = false;
    editingItem.value = null;
};

const emitUpdate = () => {
    // Always emit the current items without complex comparison
    emit('update', {
        ...props.mosaic,
        columns: columnCount.value,
        items: items.value
    });
};

// Watch for changes in column items and sync back to main items array
watch(columnItems, (newColumnItems) => {
    // Sync column items back to main items array
    const newItems: MosaicItem[] = [];
    
    newColumnItems.forEach((columnItemList, columnIndex) => {
        columnItemList.forEach((item, order) => {
            newItems.push({
                ...item,
                column_index: columnIndex,
                order: order
            });
        });
    });
    
    // Only update if there's actually a change
    if (JSON.stringify(newItems) !== JSON.stringify(items.value)) {
        items.value = newItems;
        hasChanges.value = true;
        emitUpdate();
    }
}, { deep: true });

// Watch for external changes with better handling
watch(() => props.mosaic.items, (newItems) => {
    if (JSON.stringify(newItems) !== JSON.stringify(items.value)) {
        items.value = [...(newItems || [])];
        // Force re-initialization of columns to ensure UI updates
        nextTick(() => {
            initializeColumns();
        });
    }
}, { deep: true, immediate: true });

// Also watch the entire mosaic object for other changes
watch(() => props.mosaic, (newMosaic) => {
    if (newMosaic.columns !== columnCount.value) {
        columnCount.value = newMosaic.columns || 3;
    }
    if (JSON.stringify(newMosaic.items) !== JSON.stringify(items.value)) {
        items.value = [...(newMosaic.items || [])];
        // Force re-initialization of columns to ensure UI updates
        nextTick(() => {
            initializeColumns();
        });
    }
}, { deep: true, immediate: true });
</script>

<style scoped>
.ghost-item {
    @apply opacity-50 bg-blue-100 border-2 border-blue-300 border-dashed;
}

.chosen-item {
    @apply ring-2 ring-blue-500 transform scale-105;
}

.drop-zone {
    transition: all 0.2s ease;
}

.drop-zone:hover {
    @apply border-blue-400 bg-blue-50;
}

.drop-zone.drag-over {
    @apply border-blue-500 bg-blue-100;
}
</style> 