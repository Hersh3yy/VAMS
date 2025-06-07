<template>
    <Modal v-model="show" max-width="md">
        <template #title>
            {{ item?.id ? 'Edit Item' : 'Add Item' }}
        </template>

        <div class="space-y-6">
            <!-- Item Type Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Item Type
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        v-for="type in itemTypes"
                        :key="type.value"
                        @click="selectedType = type.value"
                        class="flex items-center p-3 border rounded-lg transition-all"
                        :class="selectedType === type.value 
                            ? 'border-blue-500 bg-blue-50 text-blue-700' 
                            : 'border-gray-300 hover:border-gray-400'"
                    >
                        <span class="text-sm font-medium">{{ type.label }}</span>
                    </button>
                </div>
            </div>

            <!-- Album Selection -->
            <div v-if="selectedType === 'album'">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Select Album
                </label>
                <select 
                    v-model="selectedAlbumId"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">Choose an album...</option>
                    <option 
                        v-for="album in albums" 
                        :key="album.id" 
                        :value="album.id"
                    >
                        {{ album.title }}
                    </option>
                </select>
            </div>

            <!-- Media Upload -->
            <div v-if="selectedType === 'media'">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Upload Media
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*,video/*"
                        @change="handleFileUpload"
                        class="hidden"
                    />
                    
                    <div v-if="!uploadedMedia" class="text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <button
                            @click="fileInput?.click()"
                            class="btn-primary"
                        >
                            Choose File
                        </button>
                        <p class="text-sm text-gray-500 mt-2">PNG, JPG, GIF, MP4 up to 10MB</p>
                    </div>
                    
                    <div v-else class="text-center">
                        <img 
                            v-if="uploadedMedia.type === 'image'"
                            :src="uploadedMedia.preview" 
                            alt="Preview" 
                            class="max-h-32 mx-auto rounded mb-2"
                        />
                        <video 
                            v-else 
                            :src="uploadedMedia.preview" 
                            class="max-h-32 mx-auto rounded mb-2" 
                            controls
                        ></video>
                        <button
                            @click="removeMedia"
                            class="btn-danger text-sm"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <!-- Color Selection -->
            <div v-if="selectedType === 'color'">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Background Color
                </label>
                <input
                    v-model="selectedColor"
                    type="color"
                    class="w-full h-12 rounded-md border border-gray-300"
                />
                
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Text (Optional)
                    </label>
                    <input
                        v-model="colorText"
                        type="text"
                        placeholder="Enter text to display..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>
            </div>

            <!-- Text Content -->
            <div v-if="selectedType === 'text'">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Text Content
                </label>
                <textarea
                    v-model="textContent"
                    rows="4"
                    placeholder="Enter your text content..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                ></textarea>
            </div>

            <!-- Preview -->
            <div v-if="canPreview">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Preview
                </label>
                <div class="border rounded-lg p-4 bg-gray-50">
                    <SimpleMosaicItem :item="previewItem" />
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-3">
                <button
                    @click="$emit('close')"
                    class="btn-secondary"
                >
                    Cancel
                </button>
                <button
                    @click="handleSave"
                    class="btn-primary"
                    :disabled="!isValid"
                >
                    {{ item?.id ? 'Update' : 'Add' }} Item
                </button>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/general/Modal.vue';
import SimpleMosaicItem from './SimpleMosaicItem.vue';
import type { MosaicItem, Album } from '@/types/mosaic';

const props = defineProps<{
    show: boolean;
    item: MosaicItem | null;
    albums: Album[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', item: MosaicItem): void;
    (e: 'delete', item: MosaicItem): void;
}>();

// Form state
const selectedType = ref<string>('media');
const selectedAlbumId = ref<string>('');
const uploadedMedia = ref<{ type: 'image' | 'video'; preview: string } | null>(null);
const selectedColor = ref('#3B82F6');
const colorText = ref('');
const textContent = ref('');

// File input ref
const fileInput = ref<HTMLInputElement | null>(null);

// Item types
const itemTypes = [
    { value: 'album', label: 'Album' },
    { value: 'media', label: 'Media' },
    { value: 'color', label: 'Color' },
    { value: 'text', label: 'Text' }
];

// Computed properties
const isValid = computed(() => {
    if (selectedType.value === 'album') {
        return selectedAlbumId.value !== '';
    }
    if (selectedType.value === 'media') {
        return uploadedMedia.value !== null;
    }
    if (selectedType.value === 'text') {
        return textContent.value.trim() !== '';
    }
    return true; // Color is always valid
});

const canPreview = computed(() => {
    return isValid.value;
});

const previewItem = computed((): MosaicItem => {
    const baseItem: MosaicItem = {
        id: 'preview',
        type: selectedType.value as any,
        column_index: 0,
        order: 0,
        properties: {}
    };

    if (selectedType.value === 'album' && selectedAlbumId.value) {
        const album = props.albums.find(a => a.id.toString() === selectedAlbumId.value);
        if (album) {
            baseItem.properties = {
                album: {
                    id: album.id.toString(),
                    title: album.title,
                    cover_image_path: album.cover_image_path
                }
            };
        }
    } else if (selectedType.value === 'media' && uploadedMedia.value) {
        baseItem.properties = {
            media_url: uploadedMedia.value.preview,
            title: 'Uploaded Media'
        };
    } else if (selectedType.value === 'color') {
        baseItem.properties = {
            color: selectedColor.value,
            text: {
                enabled: true,
                content: colorText.value || '',
                color: '#000000'
            }
        };
    } else if (selectedType.value === 'text') {
        baseItem.properties = {
            text: {
                enabled: true,
                content: textContent.value || '',
                color: '#000000'
            }
        };
    }

    return baseItem;
});

// Methods
const handleFileUpload = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;

    const file = input.files[0];
    const isImage = file.type.startsWith('image/');
    const isVideo = file.type.startsWith('video/');

    if (!isImage && !isVideo) {
        alert('Please upload an image or video file');
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        uploadedMedia.value = {
            type: isImage ? 'image' : 'video',
            preview: e.target?.result as string
        };
    };
    reader.readAsDataURL(file);
};

const removeMedia = () => {
    uploadedMedia.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const handleSave = () => {
    if (!isValid.value) return;

    const item: MosaicItem = {
        id: props.item?.id || Date.now().toString(),
        type: selectedType.value as any,
        column_index: props.item?.column_index || 0,
        order: props.item?.order || 0,
        properties: previewItem.value.properties
    };

    emit('save', item);
};

// Watch for props changes
watch(() => props.item, (newItem) => {
    if (newItem) {
        selectedType.value = newItem.type;
        
        if (newItem.type === 'album' && newItem.properties?.album) {
            selectedAlbumId.value = newItem.properties.album.id;
        } else if (newItem.type === 'media' && newItem.properties?.media_url) {
            uploadedMedia.value = {
                type: 'image', // Default to image
                preview: newItem.properties.media_url
            };
        } else if (newItem.type === 'color') {
            selectedColor.value = newItem.properties?.color || '#3B82F6';
            colorText.value = (typeof newItem.properties?.text === 'string' ? newItem.properties.text : '') || '';
        } else if (newItem.type === 'text') {
            textContent.value = (typeof newItem.properties?.text === 'string' ? newItem.properties.text : '') || '';
        }
    } else {
        // Reset form
        selectedType.value = 'media';
        selectedAlbumId.value = '';
        uploadedMedia.value = null;
        selectedColor.value = '#3B82F6';
        colorText.value = '';
        textContent.value = '';
    }
}, { immediate: true });
</script>

<style scoped>
.btn-primary {
    @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors;
}

.btn-secondary {
    @apply bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md transition-colors;
}

.btn-danger {
    @apply bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition-colors;
}
</style> 