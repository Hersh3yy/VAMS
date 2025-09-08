<template>
    <div v-if="album.images && album.images.length > 0">
        <label
            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            Or Select from Album Images
        </label>

        <!-- Selected Cover Image Display -->
        <div v-if="selectedCoverImageId" class="mb-4">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div
                        class="mb-2 text-sm text-gray-600 dark:text-gray-400"
                    >
                        Selected as cover:
                    </div>
                    <div
                        class="image-container relative h-32 w-32 overflow-hidden rounded-lg border-2 border-secondary"
                    >
                        <img
                            :src="getImageUrl(selectedImage)"
                            class="cover-image"
                            alt="Selected cover"
                        />
                        <!-- Video badge for selected cover -->
                        <div
                            v-if="isVideoItem(selectedImage)"
                            class="absolute right-1 top-1 z-10 rounded-full bg-red-600 p-1 text-white"
                        >
                            <VideoPlayIcon class="h-3 w-3" />
                        </div>
                    </div>
                </div>
                <div>
                    <BaseButton
                        variant="secondary"
                        size="sm"
                        @click="$emit('clear-selection')"
                    >
                        Clear Selection
                    </BaseButton>
                </div>
            </div>
        </div>

        <!-- Image Selector -->
        <div>
            <BaseButton
                variant="secondary"
                @click="$emit('toggle-selector')"
                type="button"
            >
                {{
                    showSelector
                        ? 'Hide Images'
                        : 'Choose from Album Images'
                }}
            </BaseButton>

            <div
                v-if="showSelector"
                class="mt-4 grid max-h-64 grid-cols-4 gap-3 overflow-y-auto rounded-lg border p-4 md:grid-cols-6 lg:grid-cols-8"
            >
                <div
                    v-for="image in album.images"
                    :key="image.id"
                    @click="$emit('select-image', image)"
                    class="relative cursor-pointer overflow-hidden rounded-lg transition-all hover:ring-2 hover:ring-secondary"
                    :class="{
                        'ring-2 ring-secondary':
                            selectedCoverImageId === image.id
                    }"
                >
                    <div class="aspect-square">
                        <img
                            :src="getImageUrl(image)"
                            :alt="image.title || 'Album image'"
                            class="h-full w-full object-cover"
                        />
                        <!-- Video badge -->
                        <div
                            v-if="isVideoItem(image)"
                            class="absolute right-1 top-1 z-10 rounded-full bg-red-600 p-1 text-white"
                        >
                            <VideoPlayIcon class="h-3 w-3" />
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Click on an image or video thumbnail to select it as the
                album cover.
            </p>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue';
import BaseButton from '@/Components/Base/Button.vue';
import VideoPlayIcon from '@/Components/Base/VideoPlayIcon.vue';

const props = defineProps({
    album: {
        type: Object,
        required: true
    },
    selectedCoverImageId: {
        type: [String, Number],
        default: null
    },
    showSelector: {
        type: Boolean,
        default: false
    }
});

defineEmits(['select-image', 'clear-selection', 'toggle-selector']);

// Computed property for selected image
const selectedImage = computed(() => {
    return props.album.images?.find(img => img.id === props.selectedCoverImageId);
});

// Video handling functions (shared with ImageSelectionModal)
const isVideoItem = image => {
    if (!image) return false;

    if (image.properties) {
        const properties =
            typeof image.properties === 'string' ? JSON.parse(image.properties) : image.properties;

        return properties?.type === 'video';
    }

    // Fallback check based on path
    return (
        image.path?.includes('youtube.com') ||
        image.path?.includes('youtu.be') ||
        image.path?.includes('vimeo.com')
    );
};

const getImageUrl = image => {
    if (!image) return '/images/placeholder.svg';

    // Try to get thumbnail URL from properties (for videos)
    if (image.properties) {
        const properties =
            typeof image.properties === 'string' ? JSON.parse(image.properties) : image.properties;

        if (properties?.thumbnail_url) {
            return properties.thumbnail_url;
        }
    }

    // Fallback to regular path
    return image.path;
};
</script>
