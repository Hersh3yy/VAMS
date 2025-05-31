<template>
    <div class="text-center">
        <h4 class="text-lg font-medium text-gray-900 mb-4">
            What type of content would you like to add?
        </h4>
        <p class="text-sm text-gray-500 mb-8">
            Choose from images, videos, or text content.
        </p>
        
        <div class="grid grid-cols-3 gap-6">
            <button
                @click="selectType('image')"
                class="group relative bg-white p-6 border-2 border-gray-300 rounded-lg hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                :class="{ 'border-blue-500 bg-blue-50': selectedType === 'image' }"
            >
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg mb-4 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <svg class="w-8 h-8 text-gray-600 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/>
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" stroke-width="2"/>
                        </svg>
                    </div>
                    <h5 class="text-lg font-medium text-gray-900 mb-2">Image</h5>
                    <p class="text-sm text-gray-500">Add photos or graphics</p>
                </div>
            </button>

            <button
                @click="selectType('video')"
                class="group relative bg-white p-6 border-2 border-gray-300 rounded-lg hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                :class="{ 'border-blue-500 bg-blue-50': selectedType === 'video' }"
            >
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg mb-4 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <svg class="w-8 h-8 text-gray-600 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="m22 8-6 4 6 4V8Z" stroke-width="2"/>
                            <rect x="2" y="6" width="14" height="12" rx="2" ry="2" stroke-width="2"/>
                        </svg>
                    </div>
                    <h5 class="text-lg font-medium text-gray-900 mb-2">Video</h5>
                    <p class="text-sm text-gray-500">Embed video content</p>
                </div>
            </button>

            <button
                @click="selectType('text')"
                class="group relative bg-white p-6 border-2 border-gray-300 rounded-lg hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                :class="{ 'border-blue-500 bg-blue-50': selectedType === 'text' }"
            >
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg mb-4 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <svg class="w-8 h-8 text-gray-600 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-width="2"/>
                            <polyline points="14,2 14,8 20,8" stroke-width="2"/>
                            <line x1="16" y1="13" x2="8" y2="13" stroke-width="2"/>
                            <line x1="16" y1="17" x2="8" y2="17" stroke-width="2"/>
                            <polyline points="10,9 9,9 8,9" stroke-width="2"/>
                        </svg>
                    </div>
                    <h5 class="text-lg font-medium text-gray-900 mb-2">Text</h5>
                    <p class="text-sm text-gray-500">Add rich text content</p>
                </div>
            </button>
        </div>

        <div class="mt-8">
            <button
                v-if="selectedType"
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

const props = defineProps<{
    itemCount: number | null;
}>();

const emit = defineEmits<{
    (e: 'select', type: string): void;
}>();

const selectedType = ref<string | null>(null);

const selectType = (type: string) => {
    selectedType.value = type;
};

const confirm = () => {
    if (selectedType.value) {
        emit('select', selectedType.value);
    }
};
</script> 