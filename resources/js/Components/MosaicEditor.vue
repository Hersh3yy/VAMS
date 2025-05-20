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
                        <img 
                            :src="item.properties?.src" 
                            :alt="item.properties?.alt || ''"
                            class="w-full h-auto rounded-lg shadow-md"
                            :style="getImageStyle(item)"
                        />
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 rounded-lg">
                            <div class="absolute bottom-0 left-0 right-0 p-2 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <p class="text-sm truncate">{{ item.properties?.caption || 'Add caption' }}</p>
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
        <Modal 
            :modelValue="showItemEditor" 
            @update:modelValue="showItemEditor = $event"
            @close="closeItemEditor"
        >
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Item</h3>
                
                <!-- Image Properties -->
                <div v-if="editingItem?.type === 'image'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Caption</label>
                        <input 
                            type="text" 
                            v-model="editingItem.properties!.caption"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Image Size</label>
                        <select 
                            v-model="editingItem.properties!.size"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="cover">Cover</option>
                            <option value="contain">Contain</option>
                            <option value="fill">Fill</option>
                        </select>
                    </div>
                </div>

                <!-- Text Properties -->
                <div v-else-if="editingItem?.type === 'text'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Content</label>
                        <textarea 
                            v-model="editingItem.content"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button 
                        @click="deleteItem"
                        class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800"
                    >
                        Delete
                    </button>
                    <button 
                        @click="saveItem"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                    >
                        Save Changes
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Add Item Modal -->
        <Modal 
            :modelValue="showAddItemModal" 
            @update:modelValue="showAddItemModal = $event"
            @close="closeAddItemModal"
        >
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Item</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Item Type</label>
                        <select 
                            v-model="newItemType"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="image">Image</option>
                            <option value="text">Text</option>
                        </select>
                    </div>

                    <!-- Image Upload -->
                    <div v-if="newItemType === 'image'">
                        <label class="block text-sm font-medium text-gray-700">Upload Image</label>
                        <input 
                            type="file" 
                            @change="handleImageUpload"
                            accept="image/*"
                            class="mt-1 block w-full"
                        />
                    </div>

                    <!-- Text Input -->
                    <div v-else-if="newItemType === 'text'">
                        <label class="block text-sm font-medium text-gray-700">Content</label>
                        <textarea 
                            v-model="newItemContent"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button 
                        @click="addItem"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                    >
                        Add Item
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import Modal from '@/Components/Modal.vue'
import { useForm } from '@inertiajs/vue3'
import { v4 as uuidv4 } from 'uuid'

interface MosaicItem {
    id: string;
    type: 'image' | 'text';
    column_index: number;
    order: number;
    content?: string;
    properties?: {
        src?: string;
        alt?: string;
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
}>();

const emit = defineEmits<{
    'update': [mosaic: Mosaic];
}>();

// State
const columnCount = ref(props.mosaic.columns || 3);
const showItemEditor = ref(false);
const showAddItemModal = ref(false);
const editingItem = ref<MosaicItem | null>(null);
const newItemType = ref<'image' | 'text'>('image');
const newItemContent = ref('');
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
    showAddItemModal.value = true;
};

const closeAddItemModal = () => {
    newItemType.value = 'image';
    newItemContent.value = '';
    showAddItemModal.value = false;
};

const handleImageUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const form = useForm({
        image: file
    });

    try {
        const response = await form.post(route('api.upload-image'));
        // Handle the response and update the new item
    } catch (error) {
        console.error('Upload failed:', error);
    }
};

const addItem = () => {
    const newItem: MosaicItem = {
        id: uuidv4(),
        type: newItemType.value,
        column_index: selectedColumn.value,
        order: itemsInColumn(selectedColumn.value).length,
        content: newItemType.value === 'text' ? newItemContent.value : undefined,
        properties: newItemType.value === 'image' ? {
            src: '', // Set this after upload
            alt: '',
            caption: '',
            size: 'cover'
        } : undefined
    };

    const items = [...props.mosaic.items, newItem];
    emit('update', {
        ...props.mosaic,
        items
    });

    closeAddItemModal();
};

const saveItem = () => {
    if (!editingItem.value) return;
    
    const items = props.mosaic.items.map(item => 
        item.id === editingItem.value?.id ? editingItem.value : item
    );

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

const getImageStyle = (item: MosaicItem) => {
    const size = item.properties?.size || 'cover';
    return {
        objectFit: size
    };
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