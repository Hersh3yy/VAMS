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

            <!-- Step 1: Layout Selection -->
            <div v-if="currentStep === 1" class="p-6">
                <h4 class="text-lg font-medium mb-4">Choose Layout</h4>
                <div class="grid grid-cols-2 gap-4">
                    <button 
                        @click="selectLayout(1)"
                        class="p-4 border rounded-lg hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-blue-500': selectedLayout === 1 }"
                    >
                        <div class="aspect-square bg-gray-100 rounded"></div>
                        <p class="mt-2 text-center">Single Item</p>
                    </button>
                    <button 
                        @click="selectLayout(2)"
                        class="p-4 border rounded-lg hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-blue-500': selectedLayout === 2 }"
                    >
                        <div class="aspect-square bg-gray-100 rounded grid grid-cols-2 gap-2">
                            <div class="bg-gray-200 rounded"></div>
                            <div class="bg-gray-200 rounded"></div>
                        </div>
                        <p class="mt-2 text-center">Two Items</p>
                    </button>
                </div>
            </div>

            <!-- Step 2: Type Selection -->
            <div v-if="currentStep === 2" class="p-6">
                <h4 class="text-lg font-medium mb-4">Choose Type for Item {{ currentItemIndex + 1 }}</h4>
                <div class="grid grid-cols-3 gap-4">
                    <button 
                        @click="selectType('media')"
                        class="p-4 border rounded-lg hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-blue-500': selectedType === 'media' }"
                    >
                        <div class="aspect-square bg-gray-100 rounded flex items-center justify-center">
                            <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-center">Image/Video</p>
                    </button>
                    <button 
                        @click="selectType('color')"
                        class="p-4 border rounded-lg hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="{ 'border-blue-500': selectedType === 'color' }"
                    >
                        <div class="aspect-square bg-gray-100 rounded flex items-center justify-center">
                            <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        </div>
                        <p class="mt-2 text-center">Color Block</p>
                    </button>
                </div>
            </div>

            <!-- Step 3: Content Selection -->
            <div v-if="currentStep === 3" class="p-6">
                <h4 class="text-lg font-medium mb-4">Select Content</h4>
                
                <!-- Image Selection -->
                <div v-if="selectedType === 'media'" class="space-y-4">
                    <div class="flex space-x-4 mb-4">
                        <button 
                            @click="contentSource = 'upload'"
                            class="px-4 py-2 border rounded-lg"
                            :class="{ 'border-blue-500 bg-blue-50': contentSource === 'upload' }"
                        >
                            Upload Image
                        </button>
                        <button 
                            @click="contentSource = 'album'"
                            class="px-4 py-2 border rounded-lg"
                            :class="{ 'border-blue-500 bg-blue-50': contentSource === 'album' }"
                        >
                            Choose from Album
                        </button>
                        <button 
                            @click="contentSource = 'video'"
                            class="px-4 py-2 border rounded-lg"
                            :class="{ 'border-blue-500 bg-blue-50': contentSource === 'video' }"
                        >
                            Add Video
                        </button>
                    </div>

                    <!-- Upload Section -->
                    <div v-if="contentSource === 'upload'" class="border-2 border-dashed rounded-lg p-6 text-center">
                        <input 
                            type="file" 
                            ref="fileInput"
                            class="hidden" 
                            accept="image/*"
                            @change="handleFileUpload"
                        >
                        <button 
                            @click="fileInput?.click()"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                        >
                            Select Image
                        </button>
                    </div>

                    <!-- Album Selection -->
                    <div v-if="contentSource === 'album'" class="grid grid-cols-3 gap-4">
                        <div 
                            v-for="album in albums" 
                            :key="album.id"
                            class="aspect-square cursor-pointer"
                            @click="selectAlbum(album)"
                        >
                            <img 
                                :src="album.cover_image_path || '/placeholder.jpg'"
                                :alt="album.title"
                                class="w-full h-full object-cover rounded-lg"
                            />
                            <p class="mt-2 text-center">{{ album.title }}</p>
                        </div>
                    </div>

                    <!-- Video Selection -->
                    <div v-if="contentSource === 'video'" class="space-y-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Video URL</label>
                            <input 
                                type="text"
                                v-model="videoUrl"
                                placeholder="Enter YouTube or Vimeo URL"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Color Selection -->
                <div v-if="selectedType === 'color'" class="space-y-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Color</label>
                        <input 
                            type="color"
                            v-model="selectedColor"
                            class="mt-1 block w-full h-12 rounded-md shadow-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Step 4: Text and Link -->
            <div v-if="currentStep === 4" class="p-6">
                <h4 class="text-lg font-medium mb-4">Add Text and Link</h4>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Text Overlay</label>
                        <textarea
                            v-model="textContent"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter text to display over the item"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Text Color</label>
                        <input 
                            type="color"
                            v-model="textColor"
                            class="mt-1 block w-full h-12 rounded-md shadow-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Link URL (optional)</label>
                        <input 
                            type="url"
                            v-model="linkUrl"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="https://..."
                        />
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
                        {{ isLastStep ? 'Finish' : 'Next' }}
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
};

const fileInput = ref<HTMLInputElement | null>(null);

const handleFileUpload = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;
    // Handle file upload
};

const nextStep = () => {
    if (isLastStep.value) {
        // Save the item
        const item: Partial<MosaicItem> = {
            type: selectedType.value!,
            properties: {
                // Add properties based on type
            }
        };
        emit('save', item);
        emit('close');
    } else if (currentStep.value === 4 && selectedLayout.value === 2 && currentItemIndex.value === 0) {
        // Move to second item
        currentItemIndex.value = 1;
        currentStep.value = 2;
        selectedType.value = null;
        contentSource.value = null;
        selectedAlbum.value = null;
        videoUrl.value = '';
        selectedColor.value = '#ffffff';
        textContent.value = '';
        textColor.value = '#000000';
        linkUrl.value = '';
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
</script> 