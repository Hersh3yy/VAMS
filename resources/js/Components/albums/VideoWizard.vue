<template>
    <div class="modal-backdrop">
        <div class="modal-content max-w-4xl">
            <div class="modal-header">
                <h3 class="modal-title">Add Video</h3>
                <button
                    @click="$emit('close')"
                    class="p-2 text-2xl font-bold leading-none text-gray-400 hover:text-gray-600"
                >
                    ×
                </button>
            </div>

            <div class="modal-body">
                <!-- Step 1: Video URL -->
                <div v-if="currentStep === 1" class="space-y-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-yellow-200"
                            >Video URL</label
                        >
                        <input
                            type="text"
                            v-model="videoUrl"
                            placeholder="Enter YouTube or Vimeo URL"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-secondary focus:ring-secondary dark:border-gray-600 dark:bg-gray-800 dark:text-yellow-200"
                            @input="fetchThumbnail"
                        />
                        <p
                            class="mt-2 text-sm text-gray-500 dark:text-yellow-400"
                        >
                            Supported formats: YouTube and Vimeo links
                        </p>
                    </div>

                    <div v-if="thumbnailUrl" class="mt-4">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-yellow-200"
                            >Video Thumbnail</label
                        >
                        <img
                            :src="thumbnailUrl"
                            alt="Video thumbnail"
                            class="mt-2 w-full max-w-md rounded-lg"
                        />
                    </div>
                </div>

                <!-- Step 2: Video Details -->
                <div v-if="currentStep === 2" class="space-y-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-yellow-200"
                            >Title</label
                        >
                        <input
                            type="text"
                            v-model="title"
                            placeholder="Enter video title"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-secondary focus:ring-secondary dark:border-gray-600 dark:bg-gray-800 dark:text-yellow-200"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-yellow-200"
                            >Caption</label
                        >
                        <textarea
                            v-model="caption"
                            rows="3"
                            placeholder="Enter video description"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-secondary focus:ring-secondary dark:border-gray-600 dark:bg-gray-800 dark:text-yellow-200"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="modal-footer">
                <button
                    v-if="currentStep > 1"
                    @click="previousStep"
                    class="btn-secondary"
                >
                    Back
                </button>
                <div class="flex space-x-4">
                    <button @click="$emit('close')" class="btn-secondary">
                        Cancel
                    </button>
                    <button
                        @click="nextStep"
                        class="btn-primary"
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
import { computed, ref } from 'vue';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (
        e: 'save',
        data: {
            url: string;
            title: string;
            caption: string;
            thumbnail_url?: string;
        },
    ): void;
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
    return /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+/.test(
        url,
    );
};

const getVideoThumbnailUrl = (url: string): string | null => {
    // YouTube patterns
    const youtubeMatch = url.match(
        /(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/,
    );
    if (youtubeMatch) {
        return `https://img.youtube.com/vi/${youtubeMatch[1]}/maxresdefault.jpg`;
    }

    // Vimeo patterns
    const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
    if (vimeoMatch) {
        // For Vimeo, we'll need to use their API, but for now, return a placeholder
        // In a real implementation, you'd fetch from Vimeo's API
        return `https://vumbnail.com/${vimeoMatch[1]}.jpg`;
    }

    return null;
};

const fetchThumbnail = () => {
    if (!isValidVideoUrl(videoUrl.value)) {
        thumbnailUrl.value = '';
        return;
    }

    const thumbnail = getVideoThumbnailUrl(videoUrl.value);
    if (thumbnail) {
        thumbnailUrl.value = thumbnail;
    }
};

const nextStep = () => {
    if (currentStep.value === 1) {
        currentStep.value = 2;
    } else {
        emit('save', {
            url: videoUrl.value,
            title: title.value,
            caption: caption.value,
            thumbnail_url: thumbnailUrl.value,
        });
    }
};

const previousStep = () => {
    currentStep.value--;
};
</script>
