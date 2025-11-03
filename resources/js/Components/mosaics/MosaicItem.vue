<template>
    <div
        class="group relative cursor-pointer overflow-hidden rounded-lg"
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
            <!-- Video Item -->
            <template
                v-if="
                    item.properties?.selected_image && isVideoItem(item.properties.selected_image)
                "
            >
                <div class="relative h-full w-full">
                    <ImageDisplay
                        :image-src="
                            item.properties.selected_image.properties?.thumbnail_url ||
                            item.properties?.album?.cover_image_path ||
                            '/images/placeholder.svg'
                        "
                        :alt-text="
                            item.properties.selected_image.title ||
                            item.properties?.album?.title ||
                            'Video thumbnail'
                        "
                        :object-position="item.properties?.media?.position"
                        :scale="item.properties?.media?.scale"
                    />
                    <VideoBadge />
                </div>
            </template>
            <!-- Regular Album Item -->
            <template v-else>
                <ImageDisplay
                    :image-src="
                        item.properties?.selected_image?.path ||
                        item.properties?.album?.cover_image_path ||
                        '/placeholder.jpg'
                    "
                    :alt-text="
                        item.properties?.selected_image?.title ||
                        item.properties?.album?.title ||
                        'Album image'
                    "
                    :object-position="item.properties?.media?.position"
                    :scale="item.properties?.media?.scale"
                />
            </template>
            <!-- Album Title Overlay -->
            <div
                class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-40 opacity-0 transition-opacity group-hover:opacity-100"
            >
                <h3 class="text-lg font-medium text-white">
                    {{ albumDisplayText }}
                </h3>
            </div>
        </template>

        <!-- Media Item -->
        <template v-else-if="item.type === 'media'">
            <ImageDisplay
                v-if="item.properties?.media?.type === 'image'"
                :image-src="item.properties?.media?.path || ''"
                :alt-text="item.properties?.text?.content || ''"
                :object-position="item.properties?.media?.position"
                :scale="item.properties?.media?.scale"
            />
            <video
                v-else
                :src="item.properties?.media?.path || ''"
                class="h-full w-full object-cover"
                controls
            />
        </template>

        <!-- Color Item -->
        <template v-else-if="item.type === 'color'">
            <div
                class="flex h-full w-full items-center justify-center"
                :style="{
                    backgroundColor: item.properties?.color || '#ffffff'
                }"
            >
                <span
                    v-if="item.properties?.text?.enabled"
                    class="text-lg font-medium"
                    :style="{
                        color: item.properties?.text?.color || '#000000'
                    }"
                >
                    {{ item.properties?.text?.content }}
                </span>
            </div>
        </template>

        <!-- Text Overlay -->
        <TextOverlay
            :enabled="item.properties?.text?.enabled"
            :content="item.properties?.text?.content"
            :text-color="item.properties?.text?.color"
        />

        <!-- Delete Button -->
        <Button
            variant="danger"
            size="sm"
            icon-only
            class="absolute right-2 top-2 z-30 opacity-0 transition-opacity group-hover:opacity-100 rounded-full"
            @click.stop="$emit('delete', item)"
        >
            <Icon name="trash" size="sm" />
        </Button>
    </div>
</template>

<script setup lang="ts">
import Button from '@/Components/Base/Button.vue';
import Icon from '@/Components/Base/Icon.vue';
import VideoBadge from '@/Components/atoms/VideoBadge.vue';
import ImageDisplay from '@/Components/molecules/ImageDisplay.vue';
import TextOverlay from '@/Components/molecules/TextOverlay.vue';
import type { MosaicItem } from '@/types/mosaic';
import { computed } from 'vue';

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

const albumDisplayText = computed(() => {
    return (
        props.item.properties?.edit_text ||
        props.item.properties?.selected_image?.caption ||
        props.item.properties?.selected_image?.title ||
        props.item.properties?.album?.title
    );
});

const isVideoItem = (image: any): boolean => {
    if (image.properties) {
        const props =
            typeof image.properties === 'string' ? JSON.parse(image.properties) : image.properties;

        return props.type === 'video';
    }

    // Fallback check based on path
    return (
        image.path?.includes('youtube.com') ||
        image.path?.includes('youtu.be') ||
        image.path?.includes('vimeo.com')
    );
};
</script>
