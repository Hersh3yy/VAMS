<template>
    <div :class="containerClasses" @click="$emit('click', $event)">
        <img :src="src" :alt="alt" :class="imageClasses" >
        >
        <slot />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    src: string;
    alt?: string;
    aspectRatio?: 'square' | 'video' | 'landscape' | 'portrait';
    hover?: boolean;
    clickable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    alt: 'Image',
    aspectRatio: 'square',
    hover: true,
    clickable: true
});

defineEmits<{
    click: [event: MouseEvent];
}>();

const containerClasses = computed(() => {
    const baseClasses = 'relative overflow-hidden bg-gray-100';
    const aspectClasses = {
        square: 'aspect-square',
        video: 'aspect-video',
        landscape: 'aspect-[16/9]',
        portrait: 'aspect-[3/4]'
    };
    const hoverClasses = props.hover ? 'group cursor-pointer' : '';
    const clickableClasses = props.clickable ? 'cursor-pointer' : '';

    return `${baseClasses} ${aspectClasses[props.aspectRatio]} ${hoverClasses} ${clickableClasses}`;
});

const imageClasses = computed(() => {
    const baseClasses = 'h-full w-full object-cover';
    const hoverClasses = props.hover
        ? 'transition-transform duration-200 group-hover:scale-105'
        : '';

    return `${baseClasses} ${hoverClasses}`;
});
</script>
