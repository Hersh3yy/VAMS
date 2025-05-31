<template>
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4 text-center">
            Select Images
        </h4>
        <p class="text-sm text-gray-500 mb-8 text-center">
            Choose up to {{ maxSelection || 'unlimited' }} image{{ (maxSelection || 0) > 1 ? 's' : '' }} from this album.
        </p>
        
        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 max-h-96 overflow-y-auto">
            <button
                v-for="image in images"
                :key="image.id"
                @click="toggleImage(image)"
                class="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border-2 transition-all duration-200"
                :class="isSelected(image) ? 'border-blue-500 ring-2 ring-blue-500' : 'border-gray-300 hover:border-blue-400'"
            >
                <img
                    :src="image.path"
                    :alt="image.title || 'Image'"
                    class="w-full h-full object-cover"
                />
                
                <!-- Selection indicator -->
                <div 
                    v-if="isSelected(image)"
                    class="absolute top-2 right-2 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center"
                >
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <!-- Selection number -->
                <div 
                    v-if="isSelected(image)"
                    class="absolute top-2 left-2 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium"
                >
                    {{ getSelectionIndex(image) + 1 }}
                </div>

                <!-- Overlay when max selection reached and not selected -->
                <div 
                    v-if="!isSelected(image) && isMaxReached"
                    class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center"
                >
                    <span class="text-white text-sm">Max reached</span>
                </div>
            </button>
        </div>

        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500 mb-4">
                Selected: {{ selectedImages.length }} / {{ maxSelection }}
            </p>
            <button
                v-if="selectedImages.length > 0"
                @click="confirm"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
                Continue with {{ selectedImages.length }} image{{ selectedImages.length > 1 ? 's' : '' }}
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { AlbumImage } from '@/types/album';

const props = defineProps<{
    images: AlbumImage[];
    maxSelection: number | null;
}>();

const emit = defineEmits<{
    (e: 'select', images: AlbumImage[]): void;
}>();

const selectedImages = ref<AlbumImage[]>([]);

const isMaxReached = computed(() => {
    return props.maxSelection && selectedImages.value.length >= props.maxSelection;
});

const isSelected = (image: AlbumImage) => {
    return selectedImages.value.some(selected => selected.id === image.id);
};

const getSelectionIndex = (image: AlbumImage) => {
    return selectedImages.value.findIndex(selected => selected.id === image.id);
};

const toggleImage = (image: AlbumImage) => {
    const index = selectedImages.value.findIndex(selected => selected.id === image.id);
    
    if (index >= 0) {
        // Remove if already selected
        selectedImages.value.splice(index, 1);
    } else if (!isMaxReached.value) {
        // Add if not at max selection
        selectedImages.value.push(image);
    }
};

const confirm = () => {
    if (selectedImages.value.length > 0) {
        emit('select', [...selectedImages.value]);
    }
};
</script> 