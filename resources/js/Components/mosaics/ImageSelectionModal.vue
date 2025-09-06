<template>
    <Modal :show="show" @update:show="value => !value && handleClose()" size="2xl">
        <template #title>
            <div class="flex items-center justify-between">
                <span>Select Album Image</span>
                <button
                    @click="handleClose"
                    class="text-gray-400 transition-colors hover:text-gray-600"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <div v-if="albums.length === 0" class="py-8 text-center">
                <svg
                    class="mx-auto mb-4 h-12 w-12 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                    />
                </svg>
                <p class="text-gray-500">No albums available. Please create an album first.</p>
            </div>

            <div v-else class="space-y-8">
                <div v-for="album in albums" :key="album.id" class="space-y-4">
                    <div class="flex items-center space-x-3 border-b pb-3">
                        <img
                            :src="album.cover_image_path || '/placeholder.jpg'"
                            :alt="album.title"
                            class="h-12 w-12 rounded-lg object-cover"
                        />
                        <div>
                            <h3 class="font-semibold text-gray-900">
                                {{ album.title }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                {{ getAlbumItemCount(album) }} items
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="album.images?.length"
                        class="grid grid-cols-4 gap-3 sm:grid-cols-6 lg:grid-cols-8"
                    >
                        <div
                            v-for="image in getFilteredImages(album)"
                            :key="image.id"
                            class="group relative aspect-square cursor-pointer overflow-hidden rounded-lg border-2 border-transparent transition-all duration-200 hover:border-blue-500"
                            @click="handleImageSelect(image)"
                        >
                            <img
                                :src="getImageUrl(image)"
                                :alt="image.title || `Image ${image.id}`"
                                class="h-full w-full object-cover"
                            />
                            <!-- Video badge -->
                            <div
                                v-if="isVideoItem(image)"
                                class="absolute right-1 top-1 z-10 rounded-full bg-red-600 p-1 text-white"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3 w-3"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div
                                class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 transition-all duration-200 group-hover:bg-opacity-30"
                            >
                                <svg
                                    class="h-6 w-6 text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100"
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
                            <div
                                v-if="image.title"
                                class="absolute bottom-0 left-0 right-0 truncate bg-black bg-opacity-60 p-1 text-xs text-white"
                            >
                                {{ image.title }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-8 text-center text-gray-500">
                        <svg
                            class="mx-auto mb-2 h-8 w-8"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <p class="text-sm">No items in this album</p>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <button
                    @click="handleClose"
                    class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
                >
                    Cancel
                </button>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import Modal from '@/Components/Base/Modal.vue';
import type { Album, AlbumImage } from '@/types/mosaic';

const props = defineProps<{
    show: boolean;
    albums: Album[];
    showImagesOnly?: boolean; // If true, only show images, not videos
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'select', image: AlbumImage): void;
}>();

const handleClose = () => {
    emit('close');
};

const handleImageSelect = (image: AlbumImage) => {
    emit('select', image);
};

const isVideoItem = (image: AlbumImage) => {
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

const getImageUrl = (image: AlbumImage) => {
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

const getFilteredImages = (album: Album) => {
    if (!album.images) return [];

    if (props.showImagesOnly) {
        // Filter out videos if showImagesOnly is true
        return album.images.filter(image => !isVideoItem(image));
    }

    // Return all items (images and videos)
    return album.images;
};

const getAlbumItemCount = (album: Album) => {
    const filteredImages = getFilteredImages(album);
    return filteredImages.length;
};
</script>
