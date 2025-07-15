<template>
    <div
        ref="el"
        class="group relative aspect-square cursor-move overflow-hidden rounded-lg bg-gray-100"
        :class="{
            'scale-105 opacity-90 ring-4 ring-blue-500': isDragging,
            'ring-4 ring-green-500': isDropTarget,
            'transition-all duration-300 ease-in-out': !isDragging,
        }"
        :style="style"
        @mousedown="startDrag"
        @touchstart="startDrag"
    >
        <img
            :src="image.path"
            :alt="image.title"
            class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
        />

        <!-- Drag Handle -->
        <div
            class="absolute right-2 top-2 rounded-full bg-white/80 p-1 opacity-0 transition-opacity group-hover:opacity-100"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4 text-gray-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16m-7 6h7"
                />
            </svg>
        </div>

        <!-- Ghost Image (shown during drag) -->
        <div
            v-if="isDragging"
            class="pointer-events-none fixed z-50"
            :style="{
                width: `${ghostSize}px`,
                height: `${ghostSize}px`,
                transform: `translate(${ghostX}px, ${ghostY}px)`,
                opacity: 0.8,
                transition: 'none',
            }"
        >
            <img
                :src="image.path"
                :alt="image.title"
                class="h-full w-full rounded-lg object-cover shadow-lg"
            />
        </div>
    </div>
</template>

<script setup>
import { useDraggable } from '@vueuse/core';
import { ref } from 'vue';

const props = defineProps({
    image: {
        type: Object,
        required: true,
    },
    isDropTarget: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['dragStart', 'dragEnd', 'updatePosition']);

const el = ref(null);
const isDragging = ref(false);
const ghostSize = ref(200); // Default size for ghost image
const ghostX = ref(0);
const ghostY = ref(0);
const clickOffset = ref({ x: 0, y: 0 }); // Track where user clicked within the image

// Limit drag area to prevent flying off screen
const constrainDrag = (value, min, max) => {
    return Math.max(min, Math.min(max, value));
};

const { x, y, style } = useDraggable(el, {
    initialValue: { x: 0, y: 0 },
    preventDefault: true,
    onStart: (e) => {
        isDragging.value = true;
        // Calculate ghost image size based on original element
        const rect = el.value.getBoundingClientRect();
        ghostSize.value = rect.width;

        // Calculate offset from where user clicked within the image
        clickOffset.value = {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
        };

        // Position ghost image at cursor, accounting for click offset
        ghostX.value = e.clientX - clickOffset.value.x;
        ghostY.value = e.clientY - clickOffset.value.y;

        emit('dragStart', props.image);
    },
    onEnd: () => {
        isDragging.value = false;
        emit('dragEnd', props.image);
        // Reset position
        x.value = 0;
        y.value = 0;
    },
    onMove: (position, e) => {
        // Constrain to viewport to prevent flying off screen
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        // Update ghost image position, accounting for click offset and constraints
        ghostX.value = constrainDrag(
            e.clientX - clickOffset.value.x,
            0,
            viewportWidth - ghostSize.value,
        );
        ghostY.value = constrainDrag(
            e.clientY - clickOffset.value.y,
            0,
            viewportHeight - ghostSize.value,
        );

        emit('updatePosition', {
            image: props.image,
            position: { x: position.x, y: position.y },
        });
    },
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

/* Add smooth transitions for position changes */
.aspect-square {
    transition:
        transform 0.3s ease,
        opacity 0.3s ease;
}

/* Add a subtle animation for the drop target */
.ring-green-500 {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(34, 197, 94, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
    }
}
</style>
