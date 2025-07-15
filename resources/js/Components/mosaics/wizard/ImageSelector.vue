<template>
    <div>
        <h4 class="mb-4 text-center text-lg font-medium text-gray-900">Select Images</h4>
        <p class="mb-8 text-center text-sm text-gray-500">
            Choose up to {{ maxSelection || 'unlimited' }} image{{
                (maxSelection || 0) > 1 ? 's' : ''
            }}
            from this album.
        </p>

        <div class="grid max-h-96 grid-cols-3 gap-4 overflow-y-auto md:grid-cols-4 lg:grid-cols-6">
            <button
                v-for="image in images"
                :key="image.id"
                @click="toggleImage(image)"
                class="group relative aspect-square overflow-hidden rounded-lg border-2 bg-gray-100 transition-all duration-200"
                :class="
                    isSelected(image)
                        ? 'border-blue-500 ring-2 ring-blue-500'
                        : 'border-gray-300 hover:border-blue-400'
                "
            >
                <img
                    :src="image.path"
                    :alt="image.title || 'Image'"
                    class="h-full w-full object-cover"
                />

                <!-- Selection indicator -->
                <div
                    v-if="isSelected(image)"
                    class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-blue-600"
                >
                    <svg
                        class="h-4 w-4 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>

                <!-- Selection number -->
                <div
                    v-if="isSelected(image)"
                    class="absolute left-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-sm font-medium text-white"
                >
                    {{ getSelectionIndex(image) + 1 }}
                </div>

                <!-- Overlay when max selection reached and not selected -->
                <div
                    v-if="!isSelected(image) && isMaxReached"
                    class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50"
                >
                    <span class="text-sm text-white">Max reached</span>
                </div>
            </button>
        </div>

        <div class="mt-8 text-center">
            <p class="mb-4 text-sm text-gray-500">
                Selected: {{ selectedImages.length }} / {{ maxSelection }}
            </p>
            <button
                v-if="selectedImages.length > 0"
                @click="confirm"
                class="btn-primary inline-flex items-center"
            >
                Continue with {{ selectedImages.length }} image{{
                    selectedImages.length > 1 ? 's' : ''
                }}
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { AlbumImage } from '@/types/album';
import { computed, ref } from 'vue';

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
