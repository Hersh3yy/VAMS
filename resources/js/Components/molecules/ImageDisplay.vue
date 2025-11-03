<template>
    <img
        :src="imageSrc"
        :alt="altText"
        class="h-full w-full object-cover"
        :style="imageStyle"
    />
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    imageSrc: string;
    altText: string;
    objectPosition?: string;
    scale?: number;
}>();

const imageStyle = computed(() => {
    const style: Record<string, string> = {};
    if (props.objectPosition) {
        style.objectPosition = props.objectPosition;
    }
    if (props.scale) {
        style.transform = `scale(${props.scale})`;
    }
    return style;
});
</script>

