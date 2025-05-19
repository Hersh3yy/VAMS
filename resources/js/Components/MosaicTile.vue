<template>
    <div 
        class="mosaic-tile relative" 
        :class="[type, { 'is-adjusting': isAdjusting }]"
        :style="tileStyle"
    >
        <!-- Image Content -->
        <div v-if="type === 'image'" 
             class="image-content"
             @mousedown="startImageDrag"
             @mousemove="handleImageDrag"
             @mouseup="stopImageDrag"
             @mouseleave="stopImageDrag">
            <img 
                :src="imageSrc" 
                :alt="imageAlt"
                class="tile-image"
                :style="imageStyle"
            />
            <div v-if="imageOverlay" class="image-overlay">
                {{ imageOverlay }}
            </div>
        </div>

        <!-- Container Content -->
        <div v-else class="container-content">
            <div v-if="splitDirection" class="split-indicator" :class="splitDirection">
                <div 
                    v-if="isAdjusting"
                    class="split-handle"
                    @mousedown="startSplitAdjust"
                    @mousemove="handleSplitAdjust"
                    @mouseup="stopSplitAdjust"
                    @mouseleave="stopSplitAdjust"
                ></div>
            </div>
        </div>

        <!-- Tile Controls -->
        <MosaicTileControls
            @split="$emit('split')"
            @image="$emit('image')"
            @delete="$emit('delete')"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import MosaicTileControls from './MosaicTileControls.vue';

const props = defineProps<{
    type: 'image' | 'container';
    position: { x: number; y: number; width: number; height: number };
    imageSrc?: string;
    imageAlt?: string;
    imagePosition?: { x: number; y: number; scale: number };
    imageOverlay?: string;
    splitDirection?: 'horizontal' | 'vertical' | null;
    splitRatio?: number;
}>();

const emit = defineEmits<{
    'split': [];
    'image': [];
    'delete': [];
    'update:position': [position: { x: number; y: number; width: number; height: number }];
    'update:imagePosition': [position: { x: number; y: number; scale: number }];
    'update:splitRatio': [ratio: number];
}>();

const isAdjusting = ref(false);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

// Add default values for image position
const defaultImagePosition = { x: 0, y: 0, scale: 1 };

const tileStyle = computed(() => ({
    left: `${props.position.x}%`,
    top: `${props.position.y}%`,
    width: `${props.position.width}%`,
    height: `${props.position.height}%`
}));

const imageStyle = computed(() => {
    const position = props.imagePosition || defaultImagePosition;
    return {
        transform: `translate(${position.x}%, ${position.y}%) scale(${position.scale})`
    };
});

// Image dragging
const startImageDrag = (e: MouseEvent) => {
    isDragging.value = true;
    dragStart.value = { x: e.clientX, y: e.clientY };
};

const handleImageDrag = (e: MouseEvent) => {
    if (!isDragging.value || !props.imagePosition) return;
    
    const deltaX = e.clientX - dragStart.value.x;
    const deltaY = e.clientY - dragStart.value.y;
    
    emit('update:imagePosition', {
        x: props.imagePosition.x + (deltaX / window.innerWidth) * 100,
        y: props.imagePosition.y + (deltaY / window.innerHeight) * 100,
        scale: props.imagePosition.scale
    });
    
    dragStart.value = { x: e.clientX, y: e.clientY };
};

const stopImageDrag = () => {
    isDragging.value = false;
};

// Split adjustment
const startSplitAdjust = (e: MouseEvent) => {
    isAdjusting.value = true;
    dragStart.value = { x: e.clientX, y: e.clientY };
};

const handleSplitAdjust = (e: MouseEvent) => {
    if (!isAdjusting.value || !props.splitDirection) return;
    
    const container = e.currentTarget as HTMLElement;
    const rect = container.getBoundingClientRect();
    
    let ratio;
    if (props.splitDirection === 'horizontal') {
        ratio = (e.clientY - rect.top) / rect.height;
    } else {
        ratio = (e.clientX - rect.left) / rect.width;
    }
    
    ratio = Math.max(0.1, Math.min(0.9, ratio));
    emit('update:splitRatio', ratio);
};

const stopSplitAdjust = () => {
    isAdjusting.value = false;
};
</script>

<style scoped>
.mosaic-tile {
    position: absolute;
    transition: all 0.3s ease;
}

.image-content {
    width: 100%;
    height: 100%;
    overflow: hidden;
    position: relative;
}

.tile-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.1s ease;
}

.image-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    font-size: 0.875rem;
}

.container-content {
    width: 100%;
    height: 100%;
    border: 2px dashed #e5e7eb;
    border-radius: 0.5rem;
}

.split-indicator {
    position: absolute;
    background: rgba(59, 130, 246, 0.2);
}

.split-indicator.horizontal {
    left: 0;
    right: 0;
    height: 10px;
    cursor: ns-resize;
}

.split-indicator.vertical {
    top: 0;
    bottom: 0;
    width: 10px;
    cursor: ew-resize;
}

.split-handle {
    position: absolute;
    background: rgb(59, 130, 246);
}

.horizontal .split-handle {
    left: 0;
    right: 0;
    height: 4px;
    top: 50%;
    transform: translateY(-50%);
}

.vertical .split-handle {
    top: 0;
    bottom: 0;
    width: 4px;
    left: 50%;
    transform: translateX(-50%);
}

.is-adjusting .split-handle {
    background: rgb(37, 99, 235);
}
</style> 