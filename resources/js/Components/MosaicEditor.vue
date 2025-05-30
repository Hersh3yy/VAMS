<template>
    <div class="mosaic-editor">
        <!-- Column Count Selector -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700">Number of Columns</label>
            <select 
                v-model="columnCount" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                @change="updateColumnCount"
            >
                <option v-for="n in 4" :key="n" :value="n + 1">{{ n + 1 }} Columns</option>
            </select>
        </div>

        <!-- Masonry Grid -->
        <div class="grid gap-4" :style="{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }">
            <div 
                v-for="columnIndex in columnCount" 
                :key="columnIndex"
                class="mosaic-column"
                @dragover.prevent
                @drop="handleDrop($event, columnIndex - 1)"
            >
                <div 
                    v-for="item in itemsInColumn(columnIndex - 1)" 
                    :key="item.id"
                    class="mosaic-item mb-4"
                    draggable="true"
                    @dragstart="handleDragStart($event, item)"
                    @click="openItemEditor(item)"
                >
                    <!-- Image Item -->
                    <div v-if="item.type === 'image'" class="relative group">
                        <div class="grid grid-cols-2 gap-2">
                            <div 
                                v-for="(image, index) in item.images" 
                                :key="index"
                                class="relative aspect-square"
                            >
                                <img 
                                    :src="image.path" 
                                    :alt="image.alt_text || ''"
                                    class="w-full h-full object-cover rounded-lg"
                                />
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 rounded-lg">
                                    <div class="absolute bottom-0 left-0 right-0 p-2 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <p class="text-sm truncate">{{ image.caption || 'Add caption' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Album Item -->
                    <div v-else-if="item.type === 'album'" class="relative group">
                        <img 
                            :src="item.album?.cover_image_path || '/placeholder.jpg'" 
                            :alt="item.album?.title || ''"
                            class="w-full h-auto rounded-lg shadow-md"
                        />
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 rounded-lg">
                            <div class="absolute bottom-0 left-0 right-0 p-2 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <p class="text-sm truncate">{{ item.album?.title || 'Album' }}</p>
                                <p class="text-xs">{{ item.album?.images_count || 0 }} images</p>
                            </div>
                        </div>
                    </div>

                    <!-- Text Item -->
                    <div v-else-if="item.type === 'text'" class="p-4 bg-white rounded-lg shadow-md">
                        <p class="text-gray-800">{{ item.content }}</p>
                    </div>
                </div>

                <!-- Add Item Button -->
                <button 
                    @click="openAddItemModal(columnIndex - 1)"
                    class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-indigo-500 hover:text-indigo-500 transition-colors duration-200"
                >
                    <span class="flex items-center justify-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Item
                    </span>
                </button>
            </div>
        </div>

        <!-- Item Editor Modal -->
        <MosaicItemEditor
            :show="showItemEditor"
            :is-editing="!!editingItem"
            :item="editingItem"
            :albums="albums"
            @update:modelValue="showItemEditor = $event"
            @close="closeItemEditor"
            @save="saveItem"
            @delete="deleteItem"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import MosaicItemEditor from '@/Components/MosaicItemEditor.vue'
import { v4 as uuidv4 } from 'uuid'

interface MosaicItem {
    id: string;
    type: 'image' | 'text' | 'album';
    column_index: number;
    order: number;
    content?: string | string[];
    album_id?: string;
    album?: any;
    images?: any[];
    properties?: {
        caption?: string;
        size?: 'cover' | 'contain' | 'fill';
    };
}

interface Mosaic {
    columns: number;
    items: MosaicItem[];
}

const props = defineProps<{
    mosaic: Mosaic;
    albums?: any[];
}>();

const emit = defineEmits<{
    'update': [mosaic: Mosaic];
}>();

// State
const columnCount = ref(props.mosaic.columns || 3);
const showItemEditor = ref(false);
const editingItem = ref<MosaicItem | null>(null);
const selectedColumn = ref(0);
const draggedItem = ref<MosaicItem | null>(null);

// Computed
const itemsInColumn = (columnIndex: number) => {
    return props.mosaic.items.filter(item => item.column_index === columnIndex)
        .sort((a, b) => a.order - b.order);
};

// Methods
const updateColumnCount = () => {
    emit('update', {
        ...props.mosaic,
        columns: columnCount.value
    });
};

const handleDragStart = (event: DragEvent, item: MosaicItem) => {
    if (!event.dataTransfer) return;
    draggedItem.value = item;
    event.dataTransfer.effectAllowed = 'move';
};

const handleDrop = (event: DragEvent, columnIndex: number) => {
    if (!draggedItem.value) return;

    const items = [...props.mosaic.items];
    const itemIndex = items.findIndex(item => item.id === draggedItem.value?.id);
    
    if (itemIndex !== -1) {
        items[itemIndex] = {
            ...items[itemIndex],
            column_index: columnIndex,
            order: items.filter(item => item.column_index === columnIndex).length
        };
        
        emit('update', {
            ...props.mosaic,
            items
        });
    }
    
    draggedItem.value = null;
};

const openItemEditor = (item: MosaicItem) => {
    editingItem.value = { ...item };
    showItemEditor.value = true;
};

const closeItemEditor = () => {
    editingItem.value = null;
    showItemEditor.value = false;
};

const openAddItemModal = (columnIndex: number) => {
    selectedColumn.value = columnIndex;
    editingItem.value = null;
    showItemEditor.value = true;
};

const saveItem = (itemData: any) => {
    const items = [...props.mosaic.items];
    
    if (editingItem.value) {
        // Update existing item
        const index = items.findIndex(item => item.id === editingItem.value?.id);
        if (index !== -1) {
            items[index] = {
                ...items[index],
                ...itemData
            };
        }
    } else {
        // Add new item
        items.push({
            id: uuidv4(),
            column_index: selectedColumn.value,
            order: itemsInColumn(selectedColumn.value).length,
            ...itemData
        });
    }

    emit('update', {
        ...props.mosaic,
        items
    });

    closeItemEditor();
};

const deleteItem = () => {
    if (!editingItem.value) return;
    
    const items = props.mosaic.items.filter(item => item.id !== editingItem.value?.id);
    
    emit('update', {
        ...props.mosaic,
        items
    });

    closeItemEditor();
};
</script>

<style scoped>
.mosaic-editor {
    @apply p-4;
}

.mosaic-column {
    @apply min-h-[200px] p-2;
}

.mosaic-item {
    @apply cursor-move;
}

.mosaic-item img {
    @apply transition-transform duration-200;
}

.mosaic-item:hover img {
    @apply transform scale-[1.02];
}
</style> 