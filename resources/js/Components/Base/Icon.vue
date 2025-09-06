<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :class="iconClasses"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
    >
        <path
            v-if="name === 'arrow-left'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M10 19l-7-7m0 0l7-7m-7 7h18"
        />
        <path
            v-else-if="name === 'video'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
        />
        <path
            v-else-if="name === 'play-circle'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
        />
        <path
            v-else-if="name === 'play-circle-outline'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
        <path
            v-else-if="name === 'trash'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
        />
        <path
            v-else-if="name === 'x'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
        />
        <path
            v-else-if="name === 'exclamation-triangle'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
        <path
            v-else-if="name === 'edit'"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
        />
    </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    name:
        | 'arrow-left'
        | 'video'
        | 'play-circle'
        | 'play-circle-outline'
        | 'trash'
        | 'x'
        | 'exclamation-triangle'
        | 'edit';
    size?: 'sm' | 'md' | 'lg';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    class: ''
});

const iconClasses = computed(() => {
    const sizeClasses = {
        sm: 'h-4 w-4',
        md: 'h-5 w-5',
        lg: 'h-6 w-6'
    };

    return `${sizeClasses[props.size]} ${props.class}`;
});
</script>
