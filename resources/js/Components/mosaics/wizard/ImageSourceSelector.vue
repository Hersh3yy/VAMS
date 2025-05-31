<template>
    <div class="text-center">
        <h4 class="text-lg font-medium text-gray-900 mb-4">
            How would you like to add images?
        </h4>
        <p class="text-sm text-gray-500 mb-8">
            Upload new images or select from existing albums.
        </p>
        
        <div class="grid grid-cols-2 gap-6">
            <button
                @click="selectSource('upload')"
                class="group relative bg-white p-6 border-2 border-gray-300 rounded-lg hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                :class="{ 'border-blue-500 bg-blue-50': selectedSource === 'upload' }"
            >
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg mb-4 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <svg class="w-8 h-8 text-gray-600 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke-width="2"/>
                            <polyline points="7,10 12,15 17,10" stroke-width="2"/>
                            <line x1="12" y1="15" x2="12" y2="3" stroke-width="2"/>
                        </svg>
                    </div>
                    <h5 class="text-lg font-medium text-gray-900 mb-2">Upload New</h5>
                    <p class="text-sm text-gray-500">Upload images from your device</p>
                </div>
            </button>

            <button
                @click="selectSource('album')"
                class="group relative bg-white p-6 border-2 border-gray-300 rounded-lg hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                :class="{ 'border-blue-500 bg-blue-50': selectedSource === 'album' }"
            >
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg mb-4 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <svg class="w-8 h-8 text-gray-600 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                            <rect x="7" y="7" width="3" height="9" stroke-width="2"/>
                            <rect x="14" y="7" width="3" height="5" stroke-width="2"/>
                        </svg>
                    </div>
                    <h5 class="text-lg font-medium text-gray-900 mb-2">From Album</h5>
                    <p class="text-sm text-gray-500">Choose from existing albums</p>
                </div>
            </button>
        </div>

        <div class="mt-8">
            <button
                v-if="selectedSource"
                @click="confirm"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
                Continue
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{
    (e: 'select', source: string): void;
}>();

const selectedSource = ref<string | null>(null);

const selectSource = (source: string) => {
    selectedSource.value = source;
};

const confirm = () => {
    if (selectedSource.value) {
        emit('select', selectedSource.value);
    }
};
</script> 