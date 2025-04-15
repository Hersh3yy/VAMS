<template>
    <div
        ref="el"
        class="aspect-square relative bg-gray-100 rounded-lg overflow-hidden cursor-move group"
        :class="{
            'ring-4 ring-blue-500 opacity-90 scale-105': isDragging,
            'ring-4 ring-green-500': isDropTarget
        }"
        :style="style"
        @mousedown="startDrag"
        @touchstart="startDrag"
    >
        <img 
            :src="image.path" 
            :alt="image.title"
            class="object-cover w-full h-full transition-transform duration-200 group-hover:scale-105"
        >
        
        <!-- Drag Handle -->
        <div class="absolute top-2 right-2 bg-white/80 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useDraggable } from '@vueuse/core';

const props = defineProps({
    image: {
        type: Object,
        required: true
    },
    isDropTarget: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['dragStart', 'dragEnd', 'updatePosition']);

const el = ref(null);
const isDragging = ref(false);

const { x, y, style } = useDraggable(el, {
    initialValue: { x: 0, y: 0 },
    preventDefault: true,
    onStart: () => {
        isDragging.value = true;
        emit('dragStart', props.image);
    },
    onEnd: () => {
        isDragging.value = false;
        emit('dragEnd', props.image);
        // Reset position
        x.value = 0;
        y.value = 0;
    },
    onMove: (position) => {
        emit('updatePosition', {
            image: props.image,
            position: { x: position.x, y: position.y }
        });
    }
});

const startDrag = (e) => {
    // Prevent default only for mouse events to allow touch scrolling
    if (e.type === 'mousedown') {
        e.preventDefault();
    }
};
</script>

<style scoped>
.group {
    transform-origin: center;
    transition: all 0.2s ease;
}

.group:hover {
    @apply ring-4 ring-blue-300;
}
</style> 