<template>
    <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg overflow-hidden shadow-xl transform w-full max-w-4xl">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-medium">Add Video</h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <!-- Step 1: Video URL -->
                <div v-if="currentStep === 1" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Video URL</label>
                        <input 
                            type="text"
                            v-model="videoUrl"
                            placeholder="Enter YouTube or Vimeo URL"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p class="mt-2 text-sm text-gray-500">Supported formats: YouTube and Vimeo links</p>
                    </div>

                    <div v-if="thumbnailUrl" class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Video Thumbnail</label>
                        <img :src="thumbnailUrl" alt="Video thumbnail" class="mt-2 w-full max-w-md rounded-lg" />
                    </div>
                </div>

                <!-- Step 2: Video Details -->
                <div v-if="currentStep === 2" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input 
                            type="text"
                            v-model="title"
                            placeholder="Enter video title"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Caption</label>
                        <textarea
                            v-model="caption"
                            rows="3"
                            placeholder="Enter video description"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
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
                        {{ isLastStep ? 'Add Video' : 'Next' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', data: { url: string; title: string; caption: string }): void;
}>();

const currentStep = ref(1);
const videoUrl = ref('');
const title = ref('');
const caption = ref('');
const thumbnailUrl = ref('');

const isLastStep = computed(() => currentStep.value === 2);

const canProceed = computed(() => {
    if (currentStep.value === 1) {
        return isValidVideoUrl(videoUrl.value);
    }
    return true;
});

const isValidVideoUrl = (url: string): boolean => {
    return /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+/.test(url);
};

const fetchThumbnail = async () => {
    if (!isValidVideoUrl(videoUrl.value)) return;
    
    try {
        const response = await axios.post('/api/video-thumbnail', { url: videoUrl.value });
        thumbnailUrl.value = response.data.thumbnail_url;
    } catch (error) {
        console.error('Failed to fetch thumbnail:', error);
    }
};

const nextStep = async () => {
    if (currentStep.value === 1) {
        await fetchThumbnail();
        currentStep.value = 2;
    } else {
        emit('save', {
            url: videoUrl.value,
            title: title.value,
            caption: caption.value
        });
    }
};

const previousStep = () => {
    currentStep.value--;
};
</script> 