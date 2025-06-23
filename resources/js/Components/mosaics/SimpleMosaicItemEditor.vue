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
                                <p class="text-sm text-gray-500">{{ albumImages.length }} images</p>
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
                    <div v-if="albumImages.length > 0" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Select Image from Album ({{ albumImages.length }} images)</label>
                        <div class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto">
                            <div 
                                v-for="image in albumImages" 
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
                    
                    <!-- Show message if album has no images (only videos) -->
                    <div v-else-if="selectedAlbum?.images && selectedAlbum.images.length > 0" class="text-center py-8">
                        <div class="text-gray-500">
                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p>This album contains only videos. Please select an album with images.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MVP: Commenting out Media Upload for now - only album images allowed
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
            -->

            <!-- MVP: Commenting out Color Selection for now - only album images allowed
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
            -->

            <!-- MVP: Commenting out Text Content for now - only album images allowed
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
            -->

            <!-- Preview -->
            <div v-if="canPreview">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Preview
                </label>
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="w-full h-32 overflow-hidden rounded">
                        <!-- Album Preview -->
                        <template v-if="selectedType === 'album' && selectedAlbum">
                            <div class="w-full h-full relative">
                                <img 
                                    :src="selectedAlbum.cover_image_path || '/placeholder.jpg'"
                                    :alt="selectedAlbum.title"
                                    class="w-full h-full object-cover"
                                />
                                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                                    <div class="text-white text-center">
                                        <h4 class="font-medium">{{ selectedAlbum.title }}</h4>
                                        <p class="text-xs">{{ selectedAlbum.images?.length || 0 }} images</p>
                                        <p v-if="linkUrl.trim()" class="text-xs mt-1 opacity-75">🔗 Clickable</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <!-- MVP: Commenting out other preview types for now - only album images allowed
                        <template v-else-if="selectedType === 'media' && uploadedMedia">
                            <img 
                                v-if="uploadedMedia.type === 'image'"
                                :src="uploadedMedia.preview" 
                                alt="Preview" 
                                class="w-full h-full object-cover"
                            />
                            <video 
                                v-else 
                                :src="uploadedMedia.preview" 
                                class="w-full h-full object-cover" 
                                muted
                            ></video>
                        </template>
                        
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
                        
                        <template v-else-if="selectedType === 'text'">
                            <div class="w-full h-full flex items-center justify-center bg-gray-50 p-4">
                                <p class="text-sm text-gray-800 text-center">
                                    {{ textContent || 'Text content' }}
                                </p>
                            </div>
                        </template>
                        -->
                    </div>
                </div>
            </div>

            <!-- Link Options -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Link (Optional)
                </label>
                <input
                    v-model="linkUrl"
                    type="text"
                    placeholder="Enter URL, album title, or path starting with '/'"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
                <p class="text-xs text-gray-500 mt-1">
                    Examples: "My Album" (album title), "/gallery" (internal path), "https://example.com" (external URL)
                </p>
            </div>

            <!-- Edit Text Field -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Display Text (Optional)
                </label>
                <input
                    v-model="editText"
                    type="text"
                    placeholder="Text to display on hover or above the item"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
                <p class="text-xs text-gray-500 mt-1">
                    This text will be shown when hovering over the mosaic item instead of the album name
                </p>
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
import { ref, computed, watch, onMounted } from 'vue';
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
const selectedType = ref<string>('album');
const selectedAlbumId = ref<string>('');
const selectedAlbum = ref<Album | null>(null);
const selectedImageId = ref<string>('');
const uploadedMedia = ref<{ type: 'image' | 'video'; preview: string; serverData?: any } | null>(null);
const selectedColor = ref('#3B82F6');
const colorText = ref('');
const textContent = ref('');
const linkUrl = ref('');
const editText = ref('');

// File input ref
const fileInput = ref<HTMLInputElement | null>(null);

// Item types
const itemTypes = [
    { value: 'album', label: 'Album' },
    // MVP: Commenting out other types for now - only album images allowed
    // { value: 'media', label: 'Media' },
    // { value: 'color', label: 'Color' },
    // { value: 'text', label: 'Text' }
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

// Filter album images to exclude videos - only show image files
const albumImages = computed(() => {
    if (!selectedAlbum.value?.images) return [];
    
    return selectedAlbum.value.images.filter(image => {
        // Check if properties field indicates this is a video
        if (image.properties) {
            try {
                const props = typeof image.properties === 'string' 
                    ? JSON.parse(image.properties) 
                    : image.properties;
                
                // If type is explicitly "video", exclude it
                if (props.type === 'video') {
                    return false;
                }
            } catch (error) {
                const props = {};
                // If properties can't be parsed, assume it's an image
            }
        }
        
        // Include all items that are not explicitly marked as video
        return true;
    });
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

        // Set the uploaded media with the server response (using server path, not base64)
        uploadedMedia.value = {
            type: isImage ? 'image' : 'video',
            preview: result.data.url || result.data.path, // Use server URL for preview
            serverData: result.data
        };

    } catch (error) {
        // Show user-friendly error message instead of console error
        alert('Unable to upload file. Please try again or choose a different file.');
    }
};

const removeMedia = () => {
    uploadedMedia.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const selectImage = (image: any) => {
    selectedImageId.value = image.id;
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
    if (!isValid.value) {
        return;
    }

    const item: MosaicItem = {
        id: props.item?.id || '', // Preserve existing ID for updates, empty for new items
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
                cover_image_path: selectedAlbum.value.cover_image_path
                // Don't include all images to reduce payload size
            }
        };
        
        // If a specific image is selected, include only that image's ID and essential data
        if (selectedImageId.value && selectedAlbum.value.images) {
            const selectedImage = selectedAlbum.value.images.find(img => img.id === selectedImageId.value);
            if (selectedImage) {
                item.properties.selected_image = {
                    id: selectedImage.id,
                    path: selectedImage.path,
                    title: selectedImage.title || null,
                    caption: selectedImage.caption || null
                    // Don't include large properties object to reduce payload
                };
            }
        }
    } else if (selectedType.value === 'media' && uploadedMedia.value?.serverData) {
        // For media, only include server data (not base64 preview)
        item.properties = {
            media: {
                type: uploadedMedia.value.type,
                path: uploadedMedia.value.serverData.path || uploadedMedia.value.serverData.url,
                mime_type: uploadedMedia.value.serverData.mime_type,
                original_name: uploadedMedia.value.serverData.original_name,
                size: uploadedMedia.value.serverData.size,
                webp_url: uploadedMedia.value.serverData.webp_url
            }
        };
    } else {
        // Use the existing preview item properties for other types
        item.properties = previewItem.value.properties;
    }

    // Add link property if provided
    if (linkUrl.value.trim()) {
        if (!item.properties) {
            item.properties = {};
        }
        (item.properties as any).link = linkUrl.value.trim();
    }

    // Add edit text property if provided
    if (editText.value.trim()) {
        if (!item.properties) {
            item.properties = {};
        }
        (item.properties as any).edit_text = editText.value.trim();
    }

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
        
        // Handle existing link
        linkUrl.value = (newItem.properties as any)?.link || '';
        editText.value = (newItem.properties as any)?.edit_text || '';
    } else {
        // Reset form
        selectedType.value = 'album';
        selectedAlbumId.value = '';
        selectedAlbum.value = null;
        selectedImageId.value = '';
        uploadedMedia.value = null;
        selectedColor.value = '#3B82F6';
        colorText.value = '';
        textContent.value = '';
        linkUrl.value = '';
        editText.value = '';
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