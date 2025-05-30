<template>
    <Modal :modelValue="show" @update:modelValue="$emit('update:modelValue', $event)" @close="$emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ isEditing ? 'Edit Item' : 'Add New Item' }}</h3>
            
            <!-- Content Type Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">What would you like to add?</label>
                <div class="grid grid-cols-3 gap-4">
                    <button
                        v-for="type in contentTypes"
                        :key="type.value"
                        @click="selectedType = type.value"
                        :class="[
                            'flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md',
                            selectedType === type.value
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50'
                        ]"
                    >
                        <component :is="type.icon" class="w-5 h-5 mr-2" />
                        {{ type.label }}
                    </button>
                </div>
            </div>

            <!-- Album Selection -->
            <div v-if="selectedType === 'album'" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div 
                        v-for="album in albums" 
                        :key="album.id"
                        @click="selectedAlbum = album"
                        class="border rounded-lg p-4 cursor-pointer transition-colors duration-200"
                        :class="[
                            selectedAlbum?.id === album.id 
                                ? 'border-indigo-500 bg-indigo-50' 
                                : 'border-gray-300 hover:border-indigo-300'
                        ]"
                    >
                        <img 
                            :src="album.cover_image_path || '/placeholder.jpg'" 
                            :alt="album.title"
                            class="w-full h-32 object-cover rounded-lg mb-2"
                        />
                        <h4 class="font-medium text-gray-900">{{ album.title }}</h4>
                        <p class="text-sm text-gray-500">{{ album.images_count }} images</p>
                    </div>
                </div>
            </div>

            <!-- Media Upload -->
            <div v-if="selectedType === 'media'" class="space-y-4">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <input 
                        type="file" 
                        ref="fileInput"
                        @change="handleMediaUpload"
                        accept="image/*,video/*"
                        class="hidden"
                    />
                    <div v-if="!uploadedMedia" class="space-y-2">
                        <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <button 
                            @click="fileInput?.click()"
                            class="text-indigo-600 hover:text-indigo-500 font-medium"
                        >
                            Click to upload image or video
                        </button>
                        <p class="text-sm text-gray-500">or drag and drop</p>
                    </div>
                    <div v-else class="relative">
                        <img 
                            v-if="uploadedMedia.type === 'image'"
                            :src="uploadedMedia.preview" 
                            class="w-full h-48 object-cover rounded-lg"
                        />
                        <video 
                            v-else
                            :src="uploadedMedia.preview"
                            class="w-full h-48 object-cover rounded-lg"
                            controls
                        />
                        <button 
                            @click="removeMedia"
                            class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Color Square -->
            <div v-if="selectedType === 'color'" class="space-y-4">
                <div class="grid grid-cols-6 gap-2">
                    <button 
                        v-for="color in colors" 
                        :key="color"
                        @click="selectedColor = color"
                        class="w-8 h-8 rounded-full border-2 transition-transform"
                        :class="[
                            selectedColor === color 
                                ? 'border-indigo-500 scale-110' 
                                : 'border-gray-300 hover:scale-105'
                        ]"
                        :style="{ backgroundColor: color }"
                    />
                </div>
            </div>

            <!-- Text Overlay -->
            <div class="mt-6 space-y-4">
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="hasText" 
                        v-model="textOverlay.enabled"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <label for="hasText" class="ml-2 block text-sm text-gray-900">
                        Add text overlay
                    </label>
                </div>

                <div v-if="textOverlay.enabled" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Text</label>
                        <input 
                            type="text" 
                            v-model="textOverlay.text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Enter your text here..."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Text Color</label>
                        <select 
                            v-model="textOverlay.color"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="white">White</option>
                            <option value="black">Black</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Link Option -->
            <div class="mt-6 space-y-4">
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="hasLink" 
                        v-model="linkOptions.enabled"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <label for="hasLink" class="ml-2 block text-sm text-gray-900">
                        Make this item clickable
                    </label>
                </div>

                <div v-if="linkOptions.enabled" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Link URL</label>
                        <input 
                            type="url" 
                            v-model="linkOptions.url"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="https://..."
                        />
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-end space-x-3">
                <button 
                    v-if="isEditing"
                    @click="$emit('delete')"
                    class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800"
                >
                    Delete
                </button>
                <button 
                    @click="handleSave"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                    :disabled="!isValid"
                >
                    {{ isEditing ? 'Save Changes' : 'Add Item' }}
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Modal from '@/Components/Modal.vue'
import axios from 'axios'

const props = defineProps<{
    show: boolean;
    isEditing: boolean;
    item?: any;
    albums?: {
        id: string;
        title: string;
        cover_image_path?: string;
        images_count: number;
    }[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    'close': [];
    'save': [item: any];
    'delete': [];
}>();

type ItemType = 'album' | 'media' | 'color';

const isValidItemType = (type: string): type is ItemType => {
    return ['album', 'media', 'color'].includes(type);
};

// State
const selectedType = ref<ItemType>('media');
const selectedAlbum = ref<any>(props.item?.album || null);
const uploadedMedia = ref<{ type: string; path: string; preview?: string } | null>(null);
const selectedColor = ref<string>(props.item?.properties?.color || '#4F46E5');
const textOverlay = ref<{ enabled: boolean; text: string; color: string }>({
    enabled: props.item?.properties?.text?.enabled || false,
    text: props.item?.properties?.text?.content || '',
    color: props.item?.properties?.text?.color || '#ffffff'
});
const linkOptions = ref<{ enabled: boolean; url: string }>({
    enabled: props.item?.properties?.link?.enabled || false,
    url: props.item?.properties?.link?.url || ''
});

// File input ref
const fileInput = ref<HTMLInputElement | null>(null);

// Content types with icons
const contentTypes: { value: ItemType; label: string; icon: string }[] = [
    { value: 'album', label: 'From Album', icon: 'AlbumIcon' },
    { value: 'media', label: 'Upload Media', icon: 'UploadIcon' },
    { value: 'color', label: 'Color Block', icon: 'ColorIcon' }
];

// Predefined colors
const colors = [
    '#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF',
    '#FFFF00', '#FF00FF', '#00FFFF', '#808080', '#800000',
    '#008000', '#000080', '#808000', '#800080', '#008080'
];

// Validation
const isValid = computed(() => {
    if (selectedType.value === 'album') {
        return selectedAlbum.value !== null;
    }
    if (selectedType.value === 'media') {
        return uploadedMedia.value !== null;
    }
    if (selectedType.value === 'color') {
        return selectedColor.value !== null;
    }
    return false;
});

// Methods
const handleMediaUpload = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;

    const file = input.files[0];
    const formData = new FormData();
    formData.append('media', file);

    try {
        const response = await axios.post('/api/media/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        uploadedMedia.value = {
            type: file.type.startsWith('video/') ? 'video' : 'image',
            path: response.data.path,
            preview: URL.createObjectURL(file)
        };
    } catch (error) {
        console.error('Error uploading media:', error);
    }
};

const removeMedia = () => {
    uploadedMedia.value = null;
};

const handleSave = () => {
    let itemData: any = {
        type: selectedType.value
    };

    switch (selectedType.value) {
        case 'album':
            if (!selectedAlbum.value) return;
            itemData = {
                ...itemData,
                album_id: selectedAlbum.value.id,
                album: selectedAlbum.value
            };
            break;

        case 'media':
            if (!uploadedMedia.value) return;
            itemData = {
                ...itemData,
                content: uploadedMedia.value.path,
                properties: {
                    media_type: uploadedMedia.value.type
                }
            };
            break;

        case 'color':
            itemData = {
                ...itemData,
                properties: {
                    color: selectedColor.value
                }
            };
            break;
    }

    // Add text overlay if enabled
    if (textOverlay.value.enabled) {
        itemData.properties = {
            ...itemData.properties,
            text: {
                enabled: true,
                content: textOverlay.value.text,
                color: textOverlay.value.color
            }
        };
    }

    // Add link if enabled
    if (linkOptions.value.enabled) {
        itemData.properties = {
            ...itemData.properties,
            link: {
                enabled: true,
                url: linkOptions.value.url
            }
        };
    }

    emit('save', itemData);
};

const handleClose = () => {
    emit('update:modelValue', false);
    emit('close');
};

const handleDelete = () => {
    emit('delete');
};

// Watch for changes in props
watch(() => props.show, (newValue) => {
    if (newValue) {
        if (props.item?.type === 'album' || props.item?.type === 'media' || props.item?.type === 'color') {
            selectedType.value = props.item.type;
        }
        selectedAlbum.value = props.item?.album || null;
        uploadedMedia.value = props.item?.type === 'media' ? {
            type: props.item.properties?.media_type || 'image',
            path: props.item.content,
            preview: props.item.content
        } : null;
        selectedColor.value = props.item?.properties?.color || '#4F46E5';
        textOverlay.value = {
            enabled: props.item?.properties?.text?.enabled || false,
            text: props.item?.properties?.text?.content || '',
            color: props.item?.properties?.text?.color || '#ffffff'
        };
        linkOptions.value = {
            enabled: props.item?.properties?.link?.enabled || false,
            url: props.item?.properties?.link?.url || ''
        };
    }
});
</script>

<style scoped>
.drag-over {
    @apply border-indigo-500 bg-indigo-50;
}
</style> 