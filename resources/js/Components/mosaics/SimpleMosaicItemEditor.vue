<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="handleBackdropClick">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-medium">
                    {{ item?.id ? 'Edit Item' : 'Add Item' }}
                </h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <!-- VIEW MODE: Show existing item with simple text editing -->
                <div v-if="isViewMode">
                    <!-- Current Image Display -->
                    <div class="space-y-4">
                        <label class="block text-sm font-medium text-gray-700">Current Image</label>
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="w-full h-48 overflow-hidden rounded">
                                <img 
                                    :src="getDisplayImageSrc()"
                                    :alt="getDisplayImageAlt()"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="mt-3 text-center">
                                <p class="text-sm font-medium text-gray-900">{{ getDisplayImageAlt() }}</p>
                                <p class="text-xs text-gray-500">Selected Album:{{ selectedAlbum?.title }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Text Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Display Text
                        </label>
                        <input
                            v-model="editText"
                            type="text"
                            placeholder="Text to display on hover"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Switch to Edit Mode Button -->
                    <div class="flex justify-center pt-4">
                        <button
                            @click="toggleEditMode"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                            Change Image or Link → Full Edit Mode
                        </button>
                    </div>
                </div>

                <!-- EDIT MODE: Full interface for album/image selection -->
                <div v-else>
                    <!-- Album Selection -->
                    <div>
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
                                <div class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto scrollbar-visible">
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
                                        <div v-if="selectedImageId === image.id" class="absolute inset-0 bg-blue-500 bg-opacity-50 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Link Options (only in edit mode) -->
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
                    </div>

                    <!-- Edit Text Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Display Text (Optional)
                        </label>
                        <input
                            v-model="editText"
                            type="text"
                            placeholder="Text to display on hover"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Switch to View Mode Button -->
                    <div class="flex justify-center pt-4" v-if="item?.id">
                        <button
                            @click="toggleEditMode"
                            class="text-gray-600 hover:text-gray-800 text-sm font-medium"
                        >
                            ← Back to Simple View
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 p-4 border-t">
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
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
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
    (e: 'update'): void;
}>();

// State
const isEditMode = ref(false);
const selectedAlbumId = ref<string>('');
const selectedAlbum = ref<Album | null>(null);
const selectedImageId = ref<string>('');
const linkUrl = ref('');
const editText = ref('');

// Computed properties
const isViewMode = computed(() => !isEditMode.value && props.item?.id);
const isValid = computed(() => {
    if (isViewMode.value) return true; // View mode is always valid for updates
    return selectedAlbumId.value !== ''; // Edit mode requires album selection
});

// Filter album images to exclude videos
const albumImages = computed(() => {
    if (!selectedAlbum.value?.images) return [];
    
    return selectedAlbum.value.images.filter(image => {
        if (image.properties) {
            try {
                const props = typeof image.properties === 'string' 
                    ? JSON.parse(image.properties) 
                    : image.properties;
                
                if (props.type === 'video') {
                    return false;
                }
            } catch (error) {
                // If properties can't be parsed, assume it's an image
            }
        }
        return true;
    });
});

// Methods
const toggleEditMode = () => {
    isEditMode.value = !isEditMode.value;
};

const handleBackdropClick = () => {
    emit('close');
};

const selectImage = (image: any) => {
    selectedImageId.value = image.id;
};

const getImageUrl = (image: any) => {
    if (image.properties) {
        const props = typeof image.properties === 'string' 
            ? JSON.parse(image.properties) 
            : image.properties;
            
        if (props.thumbnail_url) {
            return props.thumbnail_url;
        }
    }
    return image.path;
};

const getDisplayImageSrc = () => {
    if (props.item?.properties?.selected_image) {
        return getImageUrl(props.item.properties.selected_image);
    }
    return selectedAlbum.value?.cover_image_path || '/placeholder.jpg';
};

const getDisplayImageAlt = () => {
    if (props.item?.properties?.selected_image) {
        return 'Selected image:' + (props.item.properties.selected_image.title || 
               props.item.properties.selected_image.caption || '');
    }
    return selectedAlbum.value?.title || 'Album';
};

const handleSave = () => {
    if (!isValid.value) return;

    const item: MosaicItem = {
        id: props.item?.id || '',
        type: 'album',
        column_index: props.item?.column_index || 0,
        order: props.item?.order || 0,
        properties: {
            album: {
                id: selectedAlbum.value!.id.toString(),
                title: selectedAlbum.value!.title,
                cover_image_path: selectedAlbum.value!.cover_image_path
            }
        }
    };
    
    // If a specific image is selected, include it
    if (selectedImageId.value && selectedAlbum.value!.images) {
        const selectedImage = selectedAlbum.value!.images.find(img => img.id === selectedImageId.value);
        if (selectedImage) {
            item.properties!.selected_image = {
                id: selectedImage.id,
                path: selectedImage.path,
                title: selectedImage.title || null,
                caption: selectedImage.caption || null,
                properties: selectedImage.properties || null
            };
        }
    }

    // Add link (only available in edit mode)
    if (linkUrl.value.trim()) {
        (item.properties! as any).link = linkUrl.value.trim();
    }

    // Add edit text (available in both modes)
    if (editText.value.trim()) {
        (item.properties! as any).edit_text = editText.value.trim();
    }

    emit('save', item);
    
    // Emit update event to trigger parent reactivity
    emit('update');
    
    // Close the modal after saving
    emit('close');
};

// Watch for props changes
watch(() => props.item, (newItem) => {
    if (newItem) {
        // Set edit mode based on whether it's a new item or existing
        isEditMode.value = !newItem.id;
        
        if (newItem.type === 'album' && newItem.properties?.album) {
            selectedAlbumId.value = newItem.properties.album.id;
            if (newItem.properties?.selected_image?.id) {
                selectedImageId.value = newItem.properties.selected_image.id;
            }
        }
        
        linkUrl.value = (newItem.properties as any)?.link || '';
        editText.value = (newItem.properties as any)?.edit_text || '';
    } else {
        // Reset form for new items
        isEditMode.value = true; // New items start in edit mode
        selectedAlbumId.value = '';
        selectedAlbum.value = null;
        selectedImageId.value = '';
        linkUrl.value = '';
        editText.value = '';
    }
}, { immediate: true });

// Watch for selectedAlbumId changes
watch(selectedAlbumId, (newAlbumId, oldAlbumId) => {
    if (newAlbumId) {
        selectedAlbum.value = props.albums.find(album => album.id.toString() === newAlbumId) || null;
        if (oldAlbumId && oldAlbumId !== newAlbumId) {
            selectedImageId.value = '';
        }
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

.scrollbar-visible {
    scrollbar-width: auto;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.scrollbar-visible::-webkit-scrollbar {
    width: 8px;
}

.scrollbar-visible::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.scrollbar-visible::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.scrollbar-visible::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style> 