<template>
    <Head :title="mosaic.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="page-title">
                    {{ mosaic.title }}
                </h2>
                <div class="flex gap-4">
                    <button 
                        @click="saveMosaic" 
                        class="btn-primary"
                        :class="{ 'opacity-50': !hasChanges }"
                        :disabled="!hasChanges"
                    >
                        {{ hasChanges ? 'Save Changes' : 'Saved' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <MosaicEditor
                    :mosaic="{
                        ...mosaic,
                        items: mosaicItems
                    }"
                    :albums="albums"
                    @update="handleMosaicUpdate"
                />
            </div>
        </div>

        <!-- Image Selection Modal -->
        <Modal v-model="showImageModal">
            <template #title>Select Image</template>
            
            <div class="space-y-6">
                <div v-if="props.albums.length === 0" class="text-center py-4">
                    <p class="text-gray-500">No albums available. Please create an album first.</p>
                </div>
                <div v-else v-for="album in props.albums" :key="album.id" class="space-y-2">
                    <h3 class="font-medium text-gray-900">{{ album.title }} ({{ album.images?.length || 0 }} images)</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div 
                            v-for="image in album.images || []" 
                            :key="image.id"
                            class="relative aspect-square cursor-pointer group"
                            @click="selectImage(image)"
                        >
                            <img 
                                :src="getImageUrl(image)"
                                :alt="image.title || undefined"
                                class="w-full h-full object-cover rounded-lg"
                            />
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-lg">
                                <span class="text-white text-sm">Select</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MosaicEditor from '@/Components/mosaics/MosaicEditor.vue';
import Modal from '@/Components/general/Modal.vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import type { Mosaic, MosaicItem, MosaicItemProperties, Album, AlbumImage } from '@/types/mosaic';

const props = defineProps<{
    mosaic: Mosaic;
    albums: Album[];
}>();

const mosaicItems = ref<MosaicItem[]>(props.mosaic.items || []);
const showImageModal = ref(false);
const selectedItemId = ref<string | null>(null);
const hasChanges = ref(false);

const handleMosaicUpdate = (updatedMosaic: Mosaic) => {
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
    // Validate items before saving
    const validationErrors: string[] = [];
    
    mosaicItems.value.forEach((item, index) => {
        if (!item.id) {
            validationErrors.push(`Item ${index + 1}: Missing ID`);
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
        toast.error(`Validation failed:\n${validationErrors.join('\n')}`, {
            position: "top-right",
            autoClose: 5000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
        });
        return;
    }

    axios.patch(`/mosaics/${props.mosaic.id}`, {
        items: mosaicItems.value
    }).then((response) => {
        hasChanges.value = false;
        toast.success('Mosaic saved successfully', {
            position: "top-right",
            autoClose: 3000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
        });
    }).catch((error) => {
        console.error('Error saving mosaic:', error);
        
        // Extract specific error messages from validation
        let errorMessage = 'Failed to save mosaic';
        
        if (error.response?.data?.errors) {
            const validationErrors = error.response.data.errors;
            const errorDetails = Object.entries(validationErrors)
                .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                .join('\n');
            errorMessage += `\n\nValidation errors:\n${errorDetails}`;
        } else if (error.response?.data?.message) {
            errorMessage += `\n\nError: ${error.response.data.message}`;
        } else if (error.message) {
            errorMessage += `\n\nError: ${error.message}`;
        }
        
        toast.error(errorMessage, {
            position: "top-right",
            autoClose: 7000,
            hideProgressBar: false,
            closeOnClick: true,
            pauseOnHover: true,
        });
    });
};

// Watch for changes
watch(mosaicItems, () => {
    hasChanges.value = true;
}, { deep: true });
</script>