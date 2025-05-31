<template>
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4 text-center">
            Add Video Content
        </h4>
        <p class="text-sm text-gray-500 mb-8 text-center">
            Enter a YouTube or Vimeo URL to embed the video.
        </p>
        
        <div class="max-w-md mx-auto space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Video URL *
                </label>
                <input
                    v-model="videoUrl"
                    type="url"
                    placeholder="https://www.youtube.com/watch?v=..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    @input="validateUrl"
                />
                <p v-if="urlError" class="mt-1 text-sm text-red-600">{{ urlError }}</p>
                <p class="mt-1 text-xs text-gray-500">Supported: YouTube and Vimeo URLs</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Title (optional)
                </label>
                <input
                    v-model="title"
                    type="text"
                    placeholder="Video title"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Caption (optional)
                </label>
                <textarea
                    v-model="caption"
                    placeholder="Video description or caption"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                ></textarea>
            </div>

            <div class="text-center">
                <button
                    @click="submit"
                    :disabled="!isValidUrl"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white transition-colors"
                    :class="isValidUrl ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'"
                >
                    Add Video
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const emit = defineEmits<{
    (e: 'submit', data: { url: string; title?: string; caption?: string }): void;
}>();

const videoUrl = ref('');
const title = ref('');
const caption = ref('');
const urlError = ref('');

const isValidUrl = computed(() => {
    if (!videoUrl.value) return false;
    return /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+/.test(videoUrl.value);
});

const validateUrl = () => {
    if (videoUrl.value && !isValidUrl.value) {
        urlError.value = 'Please enter a valid YouTube or Vimeo URL';
    } else {
        urlError.value = '';
    }
};

const submit = () => {
    if (isValidUrl.value) {
        emit('submit', {
            url: videoUrl.value,
            title: title.value || undefined,
            caption: caption.value || undefined
        });
    }
};
</script> 