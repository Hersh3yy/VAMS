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
                            'opacity-50 cursor-not-allowed bg-gray-400 text-gray-600': !hasChanges || !hasItems || isSaving,
                            'text-white shadow-lg hover:shadow-xl hover:scale-105': hasChanges && hasItems && !isSaving,
                            'animate-pulse': (hasChanges && hasItems && !isSaving) || isSaving
                        }"
                        :style="{ 
                            backgroundColor: (hasChanges && hasItems && !isSaving) ? 'var(--secondary-color)' : undefined,
                        }"
                        :disabled="!hasChanges || !hasItems || isSaving"
                    >
                        {{ isSaving ? 'Saving...' : !hasItems ? 'Add Items First' : hasChanges ? 'Save Changes' : 'Saved' }}
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
                    @open-image-selector="openImageSelector"
                />
            </div>
        </div>

        <!-- Image Selection Modal -->
        <ImageSelectionModal
            :show="showImageModal"
            :albums="props.albums"
            @close="showImageModal = false"
            @select="selectImage"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SimpleMosaicEditor from '@/Components/mosaics/SimpleMosaicEditor.vue';
import ImageSelectionModal from '@/Components/mosaics/ImageSelectionModal.vue';
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
const isSaving = ref(false);
const isLocalUpdate = ref(false); // Flag to track when we're making local updates

// Watch for changes in props.mosaic and update local state
watch(() => props.mosaic, (newMosaic) => {
    console.log('Edit page props watcher - current items:', mosaicItems.value.length, 'new items:', (newMosaic.items || []).length);
    
    // Only update from props if we're not in the middle of a local update
    if (!isLocalUpdate.value) {
        const lengthChanged = mosaicItems.value.length !== (newMosaic.items || []).length;
        
        if (lengthChanged) {
            console.log('Props items length changed, updating local state');
            mosaicItems.value = [...(newMosaic.items || [])];
            // Only reset hasChanges if we're getting fresh data from server (not local updates)
            if (!isSaving.value) {
                console.log('Resetting hasChanges to false');
                hasChanges.value = false;
            }
        }
    } else {
        console.log('Skipping props update due to local update in progress');
    }
}, { deep: true, immediate: true });

const handleMosaicUpdate = (updatedMosaic: Mosaic) => {
    console.log('handleMosaicUpdate called with', updatedMosaic.items.length, 'items');
    
    // Simple length check first, then force update for now to fix the issue
    const hasLengthChange = mosaicItems.value.length !== updatedMosaic.items.length;
    
    // For now, always update to fix the state sync issue
    isLocalUpdate.value = true; // Mark this as a local update
    mosaicItems.value = [...updatedMosaic.items]; // Create new array reference
    hasChanges.value = true;
    
    // Debug logging
    console.log('Mosaic updated - length changed:', hasLengthChange, '- hasChanges set to true');
    
    // Reset the local update flag after a short delay
    setTimeout(() => {
        isLocalUpdate.value = false;
    }, 100);
};

const openImageSelector = (itemId: string) => {
    console.log('Opening image selector for item:', itemId);
    selectedItemId.value = itemId;
    showImageModal.value = true;
};

const selectImage = (image: AlbumImage) => {
    if (!selectedItemId.value) return;
    
    const itemIndex = mosaicItems.value.findIndex(item => item.id === selectedItemId.value);
    if (itemIndex === -1) return;

    // Get the image URL (with video thumbnail support)
    let imageUrl = image.path;
    if (image.properties) {
        const properties = typeof image.properties === 'string' 
            ? JSON.parse(image.properties) 
            : image.properties;
            
        if (properties?.thumbnail_url) {
            imageUrl = properties.thumbnail_url;
        }
    }
    
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
            has_link: false,
            // Include video properties if it's a video
            ...(image.properties && {
                selected_image: {
                    id: image.id,
                    path: image.path,
                    title: image.title,
                    caption: image.caption,
                    properties: image.properties
                }
            })
        }
    };

    hasChanges.value = true;
    showImageModal.value = false;
    selectedItemId.value = null;
};

const saveMosaic = () => {
    if (isSaving.value) return; // Prevent double submissions
    
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
        toast.error('Please check your mosaic items and try again.', {
            position: "top-right",
            autoClose: 5000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
        });
        return;
    }

    isSaving.value = true;

    // Show saving toast
    toast.info('Saving your mosaic...', {
        position: "top-right",
        autoClose: 10000,
        hideProgressBar: false,
        closeOnClick: false,
        pauseOnHover: false,
    });
    
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
        isSaving.value = false;
        
        // Update the server response includes the updated items
        if (response.data.mosaic) {
            mosaicItems.value = response.data.mosaic.items || [];
        }
        
        hasChanges.value = false;
        
        // Use Inertia router for reactive update instead of full page reload
        router.reload({
            only: ['mosaic'],
            onSuccess: () => {
                // Success handled by props watcher
            }
        });
        
        toast.success('Your mosaic has been saved successfully!', {
            position: "top-right",
            autoClose: 3000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
        });
    }).catch((error) => {
        clearTimeout(timeoutId);
        isSaving.value = false;
        
        // Extract specific error messages from validation
        let errorMessage = 'Something went wrong while saving your mosaic.';
        
        if (error.code === 'ECONNABORTED' || error.name === 'AbortError') {
            errorMessage = 'The save is taking longer than expected. Please try again in a moment.';
        } else if (error.response?.data?.errors) {
            errorMessage = 'Please check your mosaic items and try again.';
        } else if (error.response?.data?.message) {
            errorMessage = 'Unable to save your mosaic. Please try again.';
        } else if (error.message) {
            errorMessage = 'Connection error. Please check your internet and try again.';
        }
        
        toast.error(errorMessage, {
            position: "top-right",
            autoClose: 8000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
        });
    });
};

// Add a computed property to check if there are items
const hasItems = computed(() => mosaicItems.value && mosaicItems.value.length > 0);


</script>