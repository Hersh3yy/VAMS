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
            
            <button 
                @click="$emit('save')"
                class="btn-primary"
                :disabled="!hasChanges"
            >
                {{ hasChanges ? 'Save Changes' : 'Saved' }}
            </button>
        </div>

        <!-- Column Layout -->
        <div 
            class="grid gap-6"
            :style="{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }"
        >
            <div 
                v-for="columnIndex in columnCount" 
                :key="columnIndex"
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
                <draggable 
                    v-model="itemsInColumn(columnIndex - 1)" 
                    item-key="id"
                    class="space-y-4"
                    group="mosaic-items"
                    ghost-class="ghost-item"
                    chosen-class="chosen-item"
                    :animation="200"
                    @end="handleReorder"
                >
                    <template #item="{ element: item }">
                        <div class="relative group">
                            <SimpleMosaicItem
                                :item="item"
                                @click="editItem(item)"
                                @delete="deleteItem(item)"
                            />
                        </div>
                    </template>
                </draggable>

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
            @close="closeItemEditor"
            @save="saveItem"
            @delete="deleteItem"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';
import SimpleMosaicItem from './SimpleMosaicItem.vue';
import SimpleMosaicItemEditor from './SimpleMosaicItemEditor.vue';
import type { Mosaic, MosaicItem, Album } from '@/types/mosaic';

const props = defineProps<{
    mosaic: Mosaic;
    albums: Album[];
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

const itemsInColumn = (columnIndex: number) => {
    return items.value.filter(item => item.column_index === columnIndex)
        .sort((a, b) => a.order - b.order);
};

const updateColumns = () => {
    hasChanges.value = true;
    emitUpdate();
};

const addItem = (columnIndex: number) => {
    const newItem: MosaicItem = {
        id: Date.now().toString(), // Simple ID generation
        type: 'media',
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
    const existingIndex = items.value.findIndex(i => i.id === item.id);
    
    if (existingIndex !== -1) {
        items.value[existingIndex] = item;
    } else {
        items.value.push(item);
    }
    
    hasChanges.value = true;
    closeItemEditor();
    emitUpdate();
};

const closeItemEditor = () => {
    showItemEditor.value = false;
    editingItem.value = null;
};

const handleReorder = (event: any) => {
    // Update orders for all items after drag
    Object.keys(event.to.children).forEach((index) => {
        const itemId = event.to.children[index].getAttribute('data-id');
        const item = items.value.find(i => i.id === itemId);
        if (item) {
            item.order = parseInt(index);
            // Update column_index if moved between columns
            const targetColumn = parseInt(event.to.getAttribute('data-column') || '0');
            item.column_index = targetColumn;
        }
    });
    
    hasChanges.value = true;
    emitUpdate();
};

const emitUpdate = () => {
    emit('update', {
        ...props.mosaic,
        columns: columnCount.value,
        items: items.value
    });
};

// Watch for external changes
watch(() => props.mosaic.items, (newItems) => {
    items.value = [...(newItems || [])];
}, { deep: true });
</script>

<style scoped>
.ghost-item {
    @apply opacity-50 bg-blue-100 border-2 border-blue-300 border-dashed;
}

.chosen-item {
    @apply ring-2 ring-blue-500 transform scale-105;
}
</style> 