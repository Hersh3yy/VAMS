<template>
    <div
        class="group relative cursor-pointer overflow-hidden rounded-lg bg-white shadow-sm transition-shadow hover:shadow-md"
        @click="$emit('click', item)"
    >
        <!-- Item Content -->
        <div class="aspect-video bg-gray-100">
            <!-- Album Item -->
            <template v-if="item.type === 'album' && item.properties?.album">
                <!-- Check if selected image is a video -->
                <template v-if="isVideoWithProperties(item)">
                    <div
                        class="relative flex h-full w-full items-center justify-center bg-gray-800"
                    >
                        <!-- Use thumbnail if available, otherwise show video icon -->
                        <img
                            v-if="getVideoThumbnail(item)"
                            :src="getVideoThumbnail(item)"
                            :alt="
                                item.properties.selected_image?.title ||
                                'Video thumbnail'
                            "
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="p-4 text-center text-white">
                            <svg
                                class="mx-auto mb-2 h-12 w-12"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path d="M8 5v14l11-7z" />
                            </svg>
                            <p class="text-xs">
                                {{
                                    item.properties.selected_image?.title ||
                                    'Video'
                                }}
                            </p>
                        </div>
                        <!-- Video badge -->
                        <div
                            class="absolute bottom-2 right-2 flex items-center space-x-1 rounded bg-red-600 px-2 py-1 text-xs text-white"
                        >
                            <svg
                                class="h-3 w-3"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path d="M8 5v14l11-7z" />
                            </svg>
                            <span>VIDEO</span>
                        </div>
                    </div>
                </template>
                <!-- Check if selected image is a regular video URL (fallback) -->
                <template
                    v-else-if="
                        item.properties.selected_image?.path &&
                        isVideoUrl(item.properties.selected_image.path)
                    "
                >
                    <div
                        class="relative flex h-full w-full items-center justify-center bg-gray-800"
                    >
                        <div class="p-4 text-center text-white">
                            <svg
                                class="mx-auto mb-2 h-12 w-12"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path d="M8 5v14l11-7z" />
                            </svg>
                            <p class="text-xs">
                                {{
                                    item.properties.selected_image.title ||
                                    'Video'
                                }}
                            </p>
                        </div>
                        <div
                            class="absolute bottom-2 right-2 rounded bg-red-600 px-2 py-1 text-xs text-white"
                        >
                            VIDEO
                        </div>
                    </div>
                </template>
                <!-- Regular image -->
                <template v-else>
                    <img
                        :src="getImageSrc(item)"
                        :alt="getImageAlt(item)"
                        class="h-full w-full object-cover"
                        @error="handleImageError"
                    />
                </template>
                <div
                    class="absolute inset-0 z-20 flex items-center justify-center bg-black bg-opacity-40 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <h3 class="px-2 text-center text-sm font-medium text-white">
                        {{
                            item.properties?.edit_text ||
                            item.properties.selected_image?.caption ||
                            item.properties.selected_image?.title ||
                            item.properties.album.title
                        }}
                    </h3>
                </div>
            </template>

            <!-- Media Item (Updated to handle new structure) -->
            <template
                v-else-if="
                    item.type === 'media' &&
                    (item.properties?.media_url || item.properties?.media?.path)
                "
            >
                <img
                    :src="
                        item.properties.media?.path ||
                        item.properties.media_url ||
                        '/images/placeholder.svg'
                    "
                    :alt="item.properties.title || 'Media'"
                    class="h-full w-full object-cover"
                    @error="handleImageError"
                />
            </template>

            <!-- Color Item -->
            <template v-else-if="item.type === 'color'">
                <div
                    class="flex h-full w-full items-center justify-center"
                    :style="{
                        backgroundColor: item.properties?.color || '#ffffff',
                    }"
                >
                    <span
                        v-if="
                            item.properties?.text?.content ||
                            item.properties?.text
                        "
                        class="px-2 text-center text-sm font-medium"
                        :style="{
                            color: getContrastColor(
                                item.properties?.color || '#ffffff',
                            ),
                        }"
                    >
                        {{
                            typeof item.properties.text === 'string'
                                ? item.properties.text
                                : item.properties.text?.content || ''
                        }}
                    </span>
                </div>
            </template>

            <!-- Text Item -->
            <template v-else-if="item.type === 'text'">
                <div
                    class="flex h-full w-full items-center justify-center bg-gray-50 p-4"
                >
                    <p class="text-center text-sm text-gray-800">
                        {{
                            typeof item.properties?.text === 'string'
                                ? item.properties.text
                                : item.properties?.text?.content ||
                                  'Text content'
                        }}
                    </p>
                </div>
            </template>

            <!-- Placeholder for empty items -->
            <template v-else>
                <div
                    class="flex h-full w-full items-center justify-center bg-gray-100"
                >
                    <div class="text-center text-gray-400">
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
                        <p class="text-xs">Empty Item</p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Item Controls -->
        <div
            class="absolute right-2 top-2 z-30 opacity-0 transition-opacity group-hover:opacity-100"
        >
            <button
                @click.stop="$emit('delete', item)"
                class="rounded-full bg-red-600 p-1 text-white transition-colors hover:bg-red-700"
                title="Delete item"
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
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                </svg>
            </button>
        </div>

        <!-- Type Badge -->
        <div class="absolute bottom-2 left-2">
            <span
                class="rounded bg-black bg-opacity-60 px-2 py-1 text-xs text-white"
            >
                {{ item.type }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { MosaicItem } from '@/types/mosaic';

const props = defineProps<{
    item: MosaicItem;
}>();

const emit = defineEmits<{
    (e: 'click', item: MosaicItem): void;
    (e: 'delete', item: MosaicItem): void;
}>();

// Helper function to get contrasting text color
const getContrastColor = (hexColor: string): string => {
    // Remove # if present
    const color = hexColor.replace('#', '');

    // Convert to RGB
    const r = parseInt(color.substr(0, 2), 16);
    const g = parseInt(color.substr(2, 2), 16);
    const b = parseInt(color.substr(4, 2), 16);

    // Calculate luminance
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance > 0.5 ? '#000000' : '#ffffff';
};

// Helper function to get image source with fallbacks
const getImageSrc = (item: MosaicItem) => {
    if (item.properties?.selected_image) {
        // Try thumbnail URL first (for videos with thumbnails)
        if (item.properties.selected_image.properties?.thumbnail_url) {
            return item.properties.selected_image.properties.thumbnail_url;
        }
        // Use the regular path
        return item.properties.selected_image.path;
    }

    // Fallback to album cover or placeholder
    return (
        item.properties?.album?.cover_image_path || '/images/placeholder.svg'
    );
};

// Helper function to get appropriate alt text
const getImageAlt = (item: MosaicItem) => {
    if (item.properties?.selected_image) {
        return (
            item.properties.selected_image.caption ||
            item.properties.selected_image.title ||
            'Selected image'
        );
    }

    return item.properties?.album?.title || 'Album';
};

// Helper function to check if a URL is a video
const isVideoUrl = (url: string) => {
    return (
        url.includes('youtube.com') ||
        url.includes('youtu.be') ||
        url.includes('vimeo.com') ||
        url.includes('.mp4') ||
        url.includes('.mov') ||
        url.includes('.avi')
    );
};

// Helper function to check if item has video properties (parsing JSON string)
const isVideoWithProperties = (item: MosaicItem) => {
    if (!item.properties?.selected_image?.properties) return false;

    try {
        const properties =
            typeof item.properties.selected_image.properties === 'string'
                ? JSON.parse(item.properties.selected_image.properties)
                : item.properties.selected_image.properties;
        return properties?.type === 'video';
    } catch {
        return false;
    }
};

// Helper function to get video thumbnail URL
const getVideoThumbnail = (item: MosaicItem) => {
    if (!item.properties?.selected_image?.properties) return null;

    try {
        const properties =
            typeof item.properties.selected_image.properties === 'string'
                ? JSON.parse(item.properties.selected_image.properties)
                : item.properties.selected_image.properties;
        return properties?.thumbnail_url || null;
    } catch {
        return null;
    }
};

// Handle image loading errors
const handleImageError = (event: Event) => {
    const img = event.target as HTMLImageElement;
    img.src = '/images/placeholder.svg';
};
</script>

<style scoped>
.group {
    transition: all 0.2s ease-in-out;
}
</style>
