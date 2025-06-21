<template>
    <Head :title="mosaic.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="page-title">
                    {{ mosaic.title }}
                </h2>
                <div class="flex gap-4">
                    <div v-if="!hasItems" class="text-sm text-orange-600 bg-orange-50 px-3 py-2 rounded-md">
                        No items added yet. Add some items below to get started!
                    </div>
                    <button 
                        @click="saveMosaic" 
                        class="px-4 py-2 rounded-md font-medium transition-all duration-300 transform"
                        :class="{ 
                            'opacity-50 cursor-not-allowed bg-gray-400 text-gray-600': !hasChanges || !hasItems,
                            'bg-primary hover:bg-primary-dark text-white shadow-lg hover:shadow-xl hover:scale-105': hasChanges && hasItems,
                            'animate-pulse': hasChanges && hasItems
                        }"
                        :disabled="!hasChanges || !hasItems"
                    >
                        {{ !hasItems ? 'Add Items First' : hasChanges ? 'Save Changes' : 'Saved' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- MVP: SimpleMosaicEditor simplified for album images only -->
                <SimpleMosaicEditor
                    :mosaic="{
                        ...props.mosaic,
                        items: mosaicItems
                    }"
                    :albums="albums"
                    :mosaic-id="props.mosaic.id"
                    @update="handleMosaicUpdate"
                    @save="saveMosaic"
                />
            </div>
        </div>

        <!-- Image Selection Modal -->
        <Modal v-model="showImageModal" max-width="2xl">
            <template #title>Select Album Image</template>
            
            <div class="space-y-6">
                <div v-if="props.albums.length === 0" class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <p class="text-gray-500">No albums available. Please create an album first.</p>
                </div>
                
                <div v-else class="space-y-8">
                    <div v-for="album in props.albums" :key="album.id" class="space-y-4">
                        <div class="flex items-center space-x-3 border-b pb-3">
                            <img 
                                :src="album.cover_image_path || '/placeholder.jpg'"
                                :alt="album.title"
                                class="w-12 h-12 object-cover rounded-lg"
                            />
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ album.title }}</h3>
                                <p class="text-sm text-gray-500">{{ album.images?.length || 0 }} images</p>
                            </div>
                        </div>
                        
                        <div v-if="album.images?.length" class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-3">
                            <div 
                                v-for="image in album.images" 
                                :key="image.id"
                                class="relative aspect-square cursor-pointer group rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-500 transition-all duration-200"
                                @click="selectImage(image)"
                            >
                                <img 
                                    :src="getImageUrl(image)"
                                    :alt="image.title || `Image ${image.id}`"
                                    class="w-full h-full object-cover"
                                />
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div v-if="image.title" class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-60 text-white text-xs p-1 truncate">
                                    {{ image.title }}
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-8 text-gray-500">
                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm">No images in this album</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <template #footer>
                <div class="flex justify-end">
                    <button 
                        @click="showImageModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SimpleMosaicEditor from '@/Components/mosaics/SimpleMosaicEditor.vue';
import Modal from '@/Components/general/Modal.vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import type { Mosaic, MosaicItem, MosaicItemProperties, Album, AlbumImage } from '@/types/mosaic';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    mosaic: Mosaic;
    albums: Album[];
}>();

const mosaicItems = ref<MosaicItem[]>(props.mosaic.items || []);
const showImageModal = ref(false);
const selectedItemId = ref<string | null>(null);
const hasChanges = ref(false);

const handleMosaicUpdate = (updatedMosaic: Mosaic) => {
    console.log('Mosaic update received in Edit.vue:', updatedMosaic);
    console.log('Items in updated mosaic:', updatedMosaic.items);
    mosaicItems.value = updatedMosaic.items;
    hasChanges.value = true;
};

const openImageSelector = (itemId: string) => {
    selectedItemId.value = itemId;
    showImageModal.value = true;
};

const selectImage = (image: AlbumImage) => {
    if (!selectedItemId.value) return;
    
    const itemIndex = mosaicItems.value.findIndex(item => item.id === selectedItemId.value);
    if (itemIndex === -1) return;

    // Get the image URL
    const imageUrl = getImageUrl(image);
    
    mosaicItems.value[itemIndex] = {
        ...mosaicItems.value[itemIndex],
        type: 'media',
        properties: {
            ...mosaicItems.value[itemIndex].properties,
            media_url: imageUrl,
            media: {
                type: 'image',
                path: imageUrl
            },
            title: image.title || '',
            caption: image.caption || '',
            show_text: false,
            has_link: false
        }
    };

    hasChanges.value = true;
    showImageModal.value = false;
};

const getImageUrl = (image: AlbumImage) => {
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

const saveMosaic = () => {
    console.log('Starting mosaic save...', { items: mosaicItems.value });
    
    // Validate items before saving
    const validationErrors: string[] = [];
    
    mosaicItems.value.forEach((item, index) => {
        // Allow empty IDs and temporary IDs for new items - backend will generate real IDs
        if (item.id && !item.id.startsWith('temp_') && item.id.trim() === '') {
            validationErrors.push(`Item ${index + 1}: Invalid ID`);
        }
        if (!item.type) {
            validationErrors.push(`Item ${index + 1}: Missing type`);
        }
        if (typeof item.column_index !== 'number') {
            validationErrors.push(`Item ${index + 1}: Invalid column index`);
        }
        if (typeof item.order !== 'number') {
            validationErrors.push(`Item ${index + 1}: Invalid order`);
        }
    });
    
    if (validationErrors.length > 0) {
        console.error('Validation errors:', validationErrors);
        toast.error(`Validation failed:\n${validationErrors.join('\n')}`, {
            position: "top-right",
            autoClose: 5000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
        });
        return;
    }

    console.log('Validation passed, sending request...');
    
    // Clean up temporary IDs for new items before sending to backend
    const itemsToSave = mosaicItems.value.map(item => ({
        ...item,
        id: item.id?.startsWith('temp_') ? '' : item.id
    }));
    
    // Add a timeout to prevent infinite waiting
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

    axios.patch(`/mosaics/${props.mosaic.id}`, {
        items: itemsToSave
    }, {
        signal: controller.signal,
        timeout: 30000
    }).then((response) => {
        clearTimeout(timeoutId);
        console.log('Save successful:', response);
        hasChanges.value = false;
        
        // Use Inertia router for reactive update instead of full page reload
        router.reload({ only: ['mosaic'] });
        
        toast.success('Mosaic saved successfully', {
            position: "top-right",
            autoClose: 3000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
        });
    }).catch((error) => {
        clearTimeout(timeoutId);
        console.error('Error saving mosaic:', error);
        
        // Extract specific error messages from validation
        let errorMessage = 'Failed to save mosaic';
        
        if (error.code === 'ECONNABORTED' || error.name === 'AbortError') {
            errorMessage = 'Request timed out. The server may be overloaded. Please try again.';
        } else if (error.response?.data?.errors) {
            const validationErrors = error.response.data.errors;
            const errorDetails = Object.entries(validationErrors)
                .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                .join('\n');
            errorMessage = `${error.response.data.message || 'Validation failed'}\n\n${errorDetails}`;
            
            // Show debug info if available
            if (error.response.data.debug_info) {
                console.log('Debug info:', error.response.data.debug_info);
                errorMessage += `\n\nDebug: Items count: ${error.response.data.debug_info.items_count}`;
            }
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.message) {
            errorMessage = `Network error: ${error.message}`;
        }
        
        toast.error(errorMessage, {
            position: "top-right",
            autoClose: 10000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
        });
    });
};

// Add a computed property to check if there are items
const hasItems = computed(() => mosaicItems.value && mosaicItems.value.length > 0);

// Watch mosaicItems for debugging
watch(mosaicItems, (newItems) => {
    console.log('mosaicItems changed:', newItems);
}, { deep: true });
</script>