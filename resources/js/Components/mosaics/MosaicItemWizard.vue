<template>
    <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg overflow-hidden shadow-xl transform w-full max-w-4xl">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-medium">{{ isEditing ? 'Edit Item' : 'Add New Item' }}</h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Direct album selection -->
            <div v-if="currentStep === 1" class="p-6">
                <h4 class="text-lg font-medium mb-4">Select Album</h4>
                
                <!-- Album Grid -->
                <div v-if="albums.length === 0" class="text-center py-8">
                    <p class="text-gray-500">No albums available. Please create an album first.</p>
                </div>
                <div v-else class="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                    <div 
                        v-for="album in albums" 
                        :key="album.id"
                        class="aspect-square cursor-pointer border-2 border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 transition-all duration-200"
                        :class="{ 'border-blue-500 ring-2 ring-blue-200': selectedAlbum?.id === album.id }"
                        @click="selectAlbum(album)"
                    >
                        <img 
                            :src="album.cover_image_path || '/placeholder.jpg'"
                            :alt="album.title"
                            class="w-full h-full object-cover"
                        />
                        <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                            <div class="opacity-0 hover:opacity-100 transition-opacity duration-200 text-center">
                                <h3 class="text-white text-sm font-medium">{{ album.title }}</h3>
                                <p class="text-white text-xs">{{ album.images?.length || 0 }} images</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Image Selection from Selected Album -->
            <div v-if="currentStep === 2 && selectedAlbum" class="p-6">
                <h4 class="text-lg font-medium mb-4">Select Image from "{{ selectedAlbum.title }}"</h4>
                
                <div v-if="!selectedAlbum.images || selectedAlbum.images.length === 0" class="text-center py-8">
                    <p class="text-gray-500">No images in this album.</p>
                </div>
                <div v-else class="grid grid-cols-3 md:grid-cols-4 gap-3 max-h-96 overflow-y-auto">
                    <div 
                        v-for="image in selectedAlbum.images" 
                        :key="image.id"
                        class="aspect-square cursor-pointer border-2 border-gray-200 rounded overflow-hidden hover:border-blue-500 transition-all duration-200"
                        :class="{ 'border-blue-500 ring-2 ring-blue-200': selectedImageId === image.id }"
                        @click="selectedImageId = image.id"
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



            <!-- Navigation Buttons -->
            <div class="p-4 border-t flex justify-between">
                <button 
                    v-if="currentStep > 1"
                    @click="previousStep"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800"
                >
                    Back
                </button>
                <div class="flex space-x-4">
                    <button 
                        @click="$emit('close')"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="nextStep"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                        :disabled="!canProceed"
                    >
                        {{ currentStep === 1 ? 'Select Images' : 'Add to Mosaic' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Album, AlbumImage } from '@/types/album';
import type { MosaicItem } from '@/types/mosaic';

const props = defineProps<{
    show: boolean;
    isEditing: boolean;
    item?: MosaicItem;
    albums: Album[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', item: Partial<MosaicItem>): void;
}>();

// Step tracking
const currentStep = ref(1);
const currentItemIndex = ref(0);
const selectedLayout = ref(1);

// Type selection
const selectedType = ref<'media' | 'color' | 'text' | 'album' | 'video' | null>(null);
const contentSource = ref<'upload' | 'album' | 'video' | null>(null);

// Content
const videoUrl = ref('');
const selectedColor = ref('#ffffff');
const selectedAlbum = ref<Album | null>(null);
const selectedImageId = ref<string | null>(null);

// Text and link
const textContent = ref('');
const textColor = ref('#000000');
const linkUrl = ref('');

// Computed
const isLastStep = computed(() => {
    if (selectedLayout.value === 2) {
        return currentStep.value === 4 && currentItemIndex.value === 1;
    }
    return currentStep.value === 4;
});

const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            return selectedLayout.value !== null;
        case 2:
            return selectedType.value !== null;
        case 3:
            if (selectedType.value === 'media') {
                return contentSource.value !== null && 
                    (contentSource.value === 'upload' || selectedAlbum.value !== null);
            }
            if (selectedType.value === 'video') {
                return videoUrl.value !== '';
            }
            return true;
        case 4:
            return true;
        default:
            return false;
    }
});

// Methods
const selectLayout = (layout: number) => {
    selectedLayout.value = layout;
};

const selectType = (type: 'media' | 'color' | 'text' | 'album' | 'video') => {
    selectedType.value = type;
};

const selectAlbum = (album: Album) => {
    selectedAlbum.value = album;
    selectedImageId.value = null; // Reset image selection when changing albums
};

const fileInput = ref<HTMLInputElement | null>(null);

const handleFileUpload = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;
    // Handle file upload
};

const nextStep = () => {
    if (isLastStep.value) {
        // Save the album image item
        const selectedImage = selectedAlbum.value?.images?.find(img => img.id === selectedImageId.value);
        
        const item: Partial<MosaicItem> = {
            type: 'album',
            properties: {
                album: {
                    id: selectedAlbum.value!.id.toString(),
                    title: selectedAlbum.value!.title,
                    cover_image_path: selectedAlbum.value!.cover_image_path
                },
                selected_image: selectedImage ? {
                    id: selectedImage.id,
                    path: selectedImage.path,
                    title: selectedImage.title ?? undefined,
                    caption: selectedImage.caption ?? undefined
                } : undefined
            }
        };
        
        emit('save', item);
        emit('close');
    } else {
        currentStep.value++;
    }
};

const previousStep = () => {
    if (currentStep.value === 2 && currentItemIndex.value === 1) {
        currentItemIndex.value = 0;
        currentStep.value = 4;
    } else {
        currentStep.value--;
    }
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
</script> 