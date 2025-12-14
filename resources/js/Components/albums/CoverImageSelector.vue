<template>
    <div class="space-y-4">
        <label
            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
        >
            Cover Image
        </label>

        <!-- Current Cover Display -->
        <div
            v-if="currentCoverUrl && !selectedImageId"
            class="relative inline-block"
        >
            <div class="mb-2 text-sm text-gray-600 dark:text-gray-400">Current cover:</div>
            <div
                class="relative h-48 w-64 overflow-hidden rounded-lg border-2 border-gray-200 dark:border-gray-700"
            >
                <img
                    :src="currentCoverUrl"
                    alt="Current cover"
                    class="h-full w-full object-cover"
                />
            </div>
        </div>

        <!-- Selected Image Preview -->
        <div v-if="selectedImageId && selectedImage" class="relative inline-block">
            <div class="mb-2 text-sm font-semibold text-secondary">New cover (from album):</div>
            <div
                class="relative h-48 w-64 overflow-hidden rounded-lg border-2 border-secondary"
            >
                <img
                    :src="getImageUrl(selectedImage)"
                    alt="Selected cover"
                    class="h-full w-full object-cover"
                />
                <div
                    v-if="isVideoItem(selectedImage)"
                    class="absolute right-2 top-2 rounded-full bg-red-600 p-1.5 text-white"
                >
                    <svg
                        class="h-4 w-4"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"
                        />
                    </svg>
                </div>
                <button
                    type="button"
                    @click="clearSelection"
                    class="absolute left-2 top-2 rounded-full bg-red-600 p-1.5 text-white transition-colors hover:bg-red-700"
                    title="Clear selection"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Select from Album -->
        <div v-if="albumImages && albumImages.length > 0" class="space-y-3">
            <div>
                <BaseButton
                    type="button"
                    variant="secondary"
                    @click="showSelector = !showSelector"
                >
                    {{ showSelector ? 'Hide Album Images' : 'Select from Album Images' }}
                </BaseButton>

                <div
                    v-if="showSelector"
                    class="mt-4 max-h-64 space-y-3 overflow-y-auto rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Click an image below to use it as the cover:
                    </p>
                    <div
                        class="grid grid-cols-4 gap-3 md:grid-cols-6 lg:grid-cols-8"
                    >
                        <button
                            v-for="image in albumImages"
                            :key="image.id"
                            type="button"
                            @click="selectImage(image)"
                            class="group relative aspect-square overflow-hidden rounded-lg transition-all hover:ring-2 hover:ring-secondary"
                            :class="{
                                'ring-2 ring-secondary': selectedImageId === image.id
                            }"
                        >
                            <img
                                :src="getImageUrl(image)"
                                :alt="image.title || 'Album image'"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-if="isVideoItem(image)"
                                class="absolute right-1 top-1 rounded-full bg-red-600 p-1 text-white"
                            >
                                <svg
                                    class="h-3 w-3"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"
                                    />
                                </svg>
                            </div>
                            <div
                                v-if="selectedImageId === image.id"
                                class="absolute inset-0 flex items-center justify-center bg-secondary/20"
                            >
                                <svg
                                    class="h-8 w-8 text-white"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="error" class="mt-1 text-sm text-red-600">
            {{ error }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import BaseButton from '@/Components/Base/Button.vue';
import type { AlbumImage } from '@/types/album';

interface Props {
    currentCoverUrl?: string | null;
    albumImages?: AlbumImage[];
    selectedImageId?: string | number | null;
    error?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    currentCoverUrl: null,
    albumImages: () => [],
    selectedImageId: null,
    error: null
});

const emit = defineEmits<{
    'select-image': [image: AlbumImage];
    'clear-selection': [];
}>();

const showSelector = ref(false);

const selectedImage = computed(() => {
    if (!props.selectedImageId || !props.albumImages) {
        return null;
    }
    return props.albumImages.find(img => img.id === props.selectedImageId);
});

const selectImage = (image: AlbumImage) => {
    emit('select-image', image);
    showSelector.value = false;
};

const clearSelection = () => {
    emit('clear-selection');
};

const isVideoItem = (image: AlbumImage): boolean => {
    if (!image) return false;

    if (image.properties) {
        const properties =
            typeof image.properties === 'string' ? JSON.parse(image.properties) : image.properties;

        return properties?.type === 'video';
    }

    return (
        image.path?.includes('youtube.com') ||
        image.path?.includes('youtu.be') ||
        image.path?.includes('vimeo.com')
    );
};

const getImageUrl = (image: AlbumImage): string => {
    if (!image) return '/images/placeholder.svg';

    if (image.properties) {
        const properties =
            typeof image.properties === 'string' ? JSON.parse(image.properties) : image.properties;

        if (properties?.thumbnail_url) {
            return properties.thumbnail_url;
        }
    }

    return image.path || '/images/placeholder.svg';
};
</script>

