<template>
    <div
        class="relative rounded-lg overflow-hidden cursor-pointer group"
        :class="{
            'opacity-50 ring-4 ring-blue-500': isDragging && draggedItem?.id === item.id,
            'ring-4 ring-green-500': isDragOver && draggedItem?.id !== item.id
        }"
        :style="{
            aspectRatio: item.properties?.aspect_ratio || '1/1',
            height: item.properties?.height || 'auto'
        }"
        draggable="true"
        @click="$emit('click', item)"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
        @dragover.prevent
        @dragenter.prevent="handleDragEnter"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDrop"
    >
        <!-- Album Item -->
        <template v-if="item.type === 'album'">
            <!-- Check if selected image is a video -->
            <template v-if="item.properties?.selected_image && isVideoItem(item.properties.selected_image)">
                <div class="w-full h-full relative">
                    <img 
                        :src="item.properties.selected_image.properties?.thumbnail_url || item.properties?.album?.cover_image_path || '/placeholder.jpg'"
                        :alt="item.properties.selected_image.title || item.properties?.album?.title || 'Video thumbnail'"
                        class="w-full h-full object-cover"
                        :style="getImageStyle()"
                    />
                    <!-- Video badge -->
                    <div class="absolute top-2 right-2 bg-red-600 text-white text-xs px-2 py-1 rounded flex items-center space-x-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <span>VIDEO</span>
                    </div>
                </div>
            </template>
            <!-- Regular album item -->
            <template v-else>
                <img 
                    :src="item.properties?.selected_image?.path || item.properties?.album?.cover_image_path || '/placeholder.jpg'"
                    :alt="item.properties?.selected_image?.title || item.properties?.album?.title || 'Album image'"
                    class="w-full h-full object-cover"
                    :style="getImageStyle()"
                />
            </template>
            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <h3 class="text-white text-lg font-medium">{{ item.properties?.edit_text || item.properties?.selected_image?.caption || item.properties?.selected_image?.title || item.properties?.album?.title }}</h3>
            </div>
        </template>

        <!-- Media Item -->
        <template v-else-if="item.type === 'media'">
            <img 
                v-if="item.properties?.media?.type === 'image'"
                :src="item.properties?.media?.path || ''" 
                :alt="item.properties?.text?.content || ''"
                class="w-full h-full object-cover"
                :style="getImageStyle()"
            />
            <video 
                v-else
                :src="item.properties?.media?.path || ''"
                class="w-full h-full object-cover"
                controls
            />
        </template>

        <!-- Color Item -->
        <template v-else-if="item.type === 'color'">
            <div 
                class="w-full h-full flex items-center justify-center"
                :style="{ backgroundColor: item.properties?.color || '#ffffff' }"
            >
                <span v-if="item.properties?.text?.enabled" 
                      class="text-lg font-medium"
                      :style="{ color: item.properties?.text?.color || '#000000' }">
                    {{ item.properties?.text?.content }}
                </span>
            </div>
        </template>

        <!-- Text Overlay -->
        <div 
            v-if="item.properties?.text?.enabled"
            class="absolute inset-0 flex items-center justify-center p-4"
            :style="{ color: item.properties?.text?.color || '#000000' }"
        >
            <p class="text-center">{{ item.properties?.text?.content }}</p>
        </div>

        <!-- Delete Button -->
        <button 
            @click.stop="$emit('delete', item)" 
            class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity z-10"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </div>
</template>

<script setup lang="ts">
import type { MosaicItem } from '@/types/mosaic';

const props = defineProps<{
    item: MosaicItem;
    isDragging: boolean;
    isDragOver: boolean;
    draggedItem: MosaicItem | null;
    settings: {
        grid_columns: number;
        gap: number;
        padding: number;
        show_titles: boolean;
        show_captions: boolean;
    };
}>();

const emit = defineEmits<{
    (e: 'click', item: MosaicItem): void;
    (e: 'delete', item: MosaicItem): void;
    (e: 'dragstart', event: DragEvent, item: MosaicItem): void;
    (e: 'dragend'): void;
    (e: 'dragenter', event: DragEvent, item: MosaicItem): void;
    (e: 'dragleave'): void;
    (e: 'drop', event: DragEvent, item: MosaicItem): void;
}>();

const handleDragStart = (event: DragEvent) => {
    emit('dragstart', event, props.item);
};

const handleDragEnd = () => {
    emit('dragend');
};

const handleDragEnter = (event: DragEvent) => {
    emit('dragenter', event, props.item);
};

const handleDragLeave = () => {
    emit('dragleave');
};

const handleDrop = (event: DragEvent) => {
    emit('drop', event, props.item);
};

const getImageStyle = () => {
    const style: Record<string, string> = {};
    if (props.item.properties?.media?.position) {
        style.objectPosition = props.item.properties.media.position;
    }
    if (props.item.properties?.media?.scale) {
        style.transform = `scale(${props.item.properties.media.scale})`;
    }
    return style;
};

const isVideoItem = (image: any) => {
    if (image.properties) {
        const props = typeof image.properties === 'string' 
            ? JSON.parse(image.properties) 
            : image.properties;
        
        return props.type === 'video';
    }
    
    // Fallback check based on path
    return image.path?.includes('youtube.com') || 
           image.path?.includes('youtu.be') || 
           image.path?.includes('vimeo.com');
};
</script> 