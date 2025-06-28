<template>
    <div
        class="relative bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow cursor-pointer group"
        @click="$emit('click', item)"
    >
        <!-- Item Content -->
        <div class="aspect-video bg-gray-100">
            <!-- Album Item -->
            <template v-if="item.type === 'album' && item.properties?.album">
                <!-- Check if selected image is a video -->
                <template v-if="isVideoWithProperties(item)">
                    <div class="w-full h-full flex items-center justify-center bg-gray-800 relative">
                        <!-- Use thumbnail if available, otherwise show video icon -->
                        <img 
                            v-if="getVideoThumbnail(item)"
                            :src="getVideoThumbnail(item)"
                            :alt="item.properties.selected_image?.title || 'Video thumbnail'"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="text-center text-white p-4">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <p class="text-xs">{{ item.properties.selected_image?.title || 'Video' }}</p>
                        </div>
                        <!-- Video badge -->
                        <div class="absolute bottom-2 right-2 bg-red-600 text-white text-xs px-2 py-1 rounded flex items-center space-x-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <span>VIDEO</span>
                        </div>
                    </div>
                </template>
                <!-- Check if selected image is a regular video URL (fallback) -->
                <template v-else-if="item.properties.selected_image?.path && isVideoUrl(item.properties.selected_image.path)">
                    <div class="w-full h-full flex items-center justify-center bg-gray-800 relative">
                        <div class="text-center text-white p-4">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <p class="text-xs">{{ item.properties.selected_image.title || 'Video' }}</p>
                        </div>
                        <div class="absolute bottom-2 right-2 bg-red-600 text-white text-xs px-2 py-1 rounded">
                            VIDEO
                        </div>
                    </div>
                </template>
                <!-- Regular image -->
                <template v-else>
                    <img 
                        :src="getImageSrc(item)"
                        :alt="getImageAlt(item)"
                        class="w-full h-full object-cover"
                        @error="handleImageError"
                    />
                </template>
                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <h3 class="text-white text-sm font-medium text-center px-2">
                        {{ item.properties.selected_image?.caption || item.properties.selected_image?.title || item.properties.album.title }}
                    </h3>
                </div>
            </template>

            <!-- Media Item (Updated to handle new structure) -->
            <template v-else-if="item.type === 'media' && (item.properties?.media_url || item.properties?.media?.path)">
                                    <img 
                        :src="item.properties.media?.path || item.properties.media_url || '/images/placeholder.svg'" 
                        :alt="item.properties.title || 'Media'"
                        class="w-full h-full object-cover"
                        @error="handleImageError"
                    />
            </template>

            <!-- Color Item -->
            <template v-else-if="item.type === 'color'">
                <div 
                    class="w-full h-full flex items-center justify-center"
                    :style="{ backgroundColor: item.properties?.color || '#ffffff' }"
                >
                    <span 
                        v-if="item.properties?.text?.content || item.properties?.text" 
                        class="text-sm font-medium text-center px-2"
                        :style="{ color: getContrastColor(item.properties?.color || '#ffffff') }"
                    >
                        {{ typeof item.properties.text === 'string' ? item.properties.text : item.properties.text?.content || '' }}
                    </span>
                </div>
            </template>

            <!-- Text Item -->
            <template v-else-if="item.type === 'text'">
                <div class="w-full h-full flex items-center justify-center bg-gray-50 p-4">
                    <p class="text-sm text-gray-800 text-center">
                        {{ typeof item.properties?.text === 'string' ? item.properties.text : item.properties?.text?.content || 'Text content' }}
                    </p>
                </div>
            </template>

            <!-- Placeholder for empty items -->
            <template v-else>
                <div class="w-full h-full flex items-center justify-center bg-gray-100">
                    <div class="text-center text-gray-400">
                        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-xs">Empty Item</p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Item Controls -->
        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button 
                @click.stop="$emit('delete', item)"
                class="bg-red-600 text-white rounded-full p-1 hover:bg-red-700 transition-colors"
                title="Delete item"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>

        <!-- Type Badge -->
        <div class="absolute bottom-2 left-2">
            <span class="bg-black bg-opacity-60 text-white text-xs px-2 py-1 rounded">
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
    return item.properties?.album?.cover_image_path || '/images/placeholder.svg';
};

// Helper function to get appropriate alt text
const getImageAlt = (item: MosaicItem) => {
    if (item.properties?.selected_image) {
        return item.properties.selected_image.caption || 
               item.properties.selected_image.title || 
               'Selected image';
    }
    
    return item.properties?.album?.title || 'Album';
};

// Helper function to check if a URL is a video
const isVideoUrl = (url: string) => {
    return url.includes('youtube.com') || 
           url.includes('youtu.be') || 
           url.includes('vimeo.com') ||
           url.includes('.mp4') ||
           url.includes('.mov') ||
           url.includes('.avi');
};

// Helper function to check if item has video properties (parsing JSON string)
const isVideoWithProperties = (item: MosaicItem) => {
    if (!item.properties?.selected_image?.properties) return false;
    
    try {
        const properties = typeof item.properties.selected_image.properties === 'string' 
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
        const properties = typeof item.properties.selected_image.properties === 'string' 
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