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
                
                <!-- Visual Album Grid -->
                <div v-if="!selectedAlbumId" class="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-64 overflow-y-auto">
                    <div 
                        v-for="album in albums" 
                        :key="album.id"
                        @click="selectedAlbumId = album.id.toString()"
                        class="relative group cursor-pointer border-2 border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 transition-all duration-200"
                    >
                        <div class="aspect-square">
                            <img 
                                :src="album.cover_image_path || '/placeholder.jpg'"
                                :alt="album.title"
                                class="w-full h-full object-cover"
                            />
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 text-center">
                                    <h3 class="text-white text-sm font-medium">{{ album.title }}</h3>
                                    <p class="text-white text-xs">{{ album.images?.length || 0 }} images</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Selected Album Display -->
                <div v-if="selectedAlbumId" class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <img 
                                :src="selectedAlbum?.cover_image_path || '/placeholder.jpg'"
                                :alt="selectedAlbum?.title"
                                class="w-12 h-12 object-cover rounded"
                            />
                            <div>
                                <h4 class="font-medium text-gray-900">{{ selectedAlbum?.title }}</h4>
                                <p class="text-sm text-gray-500">{{ selectedAlbum?.images?.length || 0 }} images</p>
                            </div>
                        </div>
                        <button 
                            @click="selectedAlbumId = ''; selectedAlbum = null"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                            Change Album
                        </button>
                    </div>
                    
                    <!-- Album Images Grid -->
                    <div v-if="selectedAlbum?.images?.length" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Select Image from Album</label>
                        <div class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto">
                            <div 
                                v-for="image in selectedAlbum.images" 
                                :key="image.id"
                                @click="selectImage(image)"
                                class="relative aspect-square cursor-pointer border-2 border-gray-200 rounded overflow-hidden hover:border-blue-500 transition-all duration-200"
                                :class="{ 'border-blue-500 ring-2 ring-blue-200': selectedImageId === image.id }"
                            >
                                <img 
                                    :src="getImageUrl(image)"
                                    :alt="image.title || `Image ${image.id}`"
                                    class="w-full h-full object-cover"
                                />
                                <div v-if="selectedImageId === image.id" class="absolute inset-0 bg-blue-500 bg-opacity-20 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <!-- Simple Preview without SimpleMosaicItem -->
                    <div class="aspect-video bg-white rounded-lg overflow-hidden shadow-sm">
                        <!-- Album Preview -->
                        <template v-if="selectedType === 'album' && selectedAlbum">
                            <img 
                                :src="selectedImageId && selectedAlbum.images ? 
                                    getImageUrl(selectedAlbum.images.find(img => img.id === selectedImageId)) : 
                                    selectedAlbum.cover_image_path || '/placeholder.jpg'"
                                :alt="selectedImageId ? 
                                    selectedAlbum.images?.find(img => img.id === selectedImageId)?.title || 'Selected image' : 
                                    selectedAlbum.title"
                                class="w-full h-full object-cover"
                            />
                        </template>
                        
                        <!-- Media Preview -->
                        <template v-else-if="selectedType === 'media' && uploadedMedia">
                            <img 
                                :src="uploadedMedia.preview" 
                                alt="Preview"
                                class="w-full h-full object-cover"
                            />
                        </template>
                        
                        <!-- Color Preview -->
                        <template v-else-if="selectedType === 'color'">
                            <div 
                                class="w-full h-full flex items-center justify-center"
                                :style="{ backgroundColor: selectedColor }"
                            >
                                <span 
                                    v-if="colorText" 
                                    class="text-sm font-medium text-center px-2"
                                    :style="{ color: selectedColor === '#ffffff' ? '#000000' : '#ffffff' }"
                                >
                                    {{ colorText }}
                                </span>
                            </div>
                        </template>
                        
                        <!-- Text Preview -->
                        <template v-else-if="selectedType === 'text'">
                            <div class="w-full h-full flex items-center justify-center bg-gray-50 p-4">
                                <p class="text-sm text-gray-800 text-center">
                                    {{ textContent || 'Text content' }}
                                </p>
                            </div>
                        </template>
                    </div>
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
import type { MosaicItem, Album } from '@/types/mosaic';

const props = defineProps<{
    show: boolean;
    item: MosaicItem | null;
    albums: Album[];
    mosaicId?: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', item: MosaicItem): void;
    (e: 'delete', item: MosaicItem): void;
}>();

// Form state
const selectedType = ref<string>('media');
const selectedAlbumId = ref<string>('');
const selectedAlbum = ref<Album | null>(null);
const selectedImageId = ref<string>('');
const uploadedMedia = ref<{ type: 'image' | 'video'; preview: string; serverData?: any } | null>(null);
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
            media: {
                type: uploadedMedia.value.type,
                path: uploadedMedia.value.preview,
                // Include server data for proper storage
                ...(uploadedMedia.value.serverData || {})
            }
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
const handleFileUpload = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;

    const file = input.files[0];
    const isImage = file.type.startsWith('image/');
    const isVideo = file.type.startsWith('video/');

    if (!isImage && !isVideo) {
        alert('Please upload an image or video file');
        return;
    }

    try {
        // Upload the file to the backend using the same approach as albums
        const formData = new FormData();
        formData.append('media', file);

        // Get CSRF token
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(route('mosaics.media.upload', { mosaic: props.mosaicId }), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: formData
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Upload failed');
        }

        const result = await response.json();

        // Set the uploaded media with the server response
        uploadedMedia.value = {
            type: isImage ? 'image' : 'video',
            preview: result.data.path,
            serverData: result.data
        };

    } catch (error) {
        console.error('Upload failed:', error);
        alert('Failed to upload file: ' + (error as Error).message);
    }
};

const removeMedia = () => {
    uploadedMedia.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const selectImage = (image: any) => {
    console.log('Image selected:', image);
    selectedImageId.value = image.id;
    console.log('Selected image ID set to:', selectedImageId.value);
};

const getImageUrl = (image: any) => {
    // Try to get thumbnail URL from properties
    if (image.properties) {
        const props = typeof image.properties === 'string' 
            ? JSON.parse(image.properties) 
            : image.properties;
            
        if (props.thumbnail_url) {
            return props.thumbnail_url;
        }
    }
    
    // Fallback to regular path
    return image.path;
};

const handleSave = () => {
    console.log('SimpleMosaicItemEditor handleSave called');
    console.log('Form state:', {
        selectedType: selectedType.value,
        selectedAlbumId: selectedAlbumId.value,
        selectedAlbum: selectedAlbum.value,
        selectedImageId: selectedImageId.value,
        isValid: isValid.value
    });
    
    if (!isValid.value) {
        console.warn('Form is not valid, cannot save');
        return;
    }

    const item: MosaicItem = {
        id: props.item?.id || Date.now().toString(),
        type: selectedType.value as any,
        column_index: props.item?.column_index || 0,
        order: props.item?.order || 0,
        properties: {}
    };

    // Handle album selection with image
    if (selectedType.value === 'album' && selectedAlbum.value) {
        item.properties = {
            album: {
                id: selectedAlbum.value.id.toString(),
                title: selectedAlbum.value.title,
                cover_image_path: selectedAlbum.value.cover_image_path,
                images: selectedAlbum.value.images
            }
        };
        
        // If a specific image is selected, include it
        if (selectedImageId.value && selectedAlbum.value.images) {
            const selectedImage = selectedAlbum.value.images.find(img => img.id === selectedImageId.value);
            if (selectedImage) {
                console.log('Including selected image in save:', selectedImage);
                item.properties.selected_image = {
                    id: selectedImage.id,
                    path: selectedImage.path,
                    title: selectedImage.title || null,
                    caption: selectedImage.caption || null,
                    properties: selectedImage.properties
                };
            }
        }
    } else {
        // Use the existing preview item properties for other types
        item.properties = previewItem.value.properties;
    }

    console.log('Final item to save:', item);
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
            colorText.value = (typeof newItem.properties?.text === 'string' ? newItem.properties.text : newItem.properties?.text?.content || '');
        } else if (newItem.type === 'text') {
            textContent.value = (typeof newItem.properties?.text === 'string' ? newItem.properties.text : newItem.properties?.text?.content || '');
        }
    } else {
        // Reset form
        selectedType.value = 'media';
        selectedAlbumId.value = '';
        selectedAlbum.value = null;
        selectedImageId.value = '';
        uploadedMedia.value = null;
        selectedColor.value = '#3B82F6';
        colorText.value = '';
        textContent.value = '';
    }
}, { immediate: true });

// Watch for selectedAlbumId changes
watch(selectedAlbumId, (newAlbumId) => {
    if (newAlbumId) {
        selectedAlbum.value = props.albums.find(album => album.id.toString() === newAlbumId) || null;
    } else {
        selectedAlbum.value = null;
        selectedImageId.value = '';
    }
});
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