<template>
    <div
        v-if="enabled"
        class="absolute inset-0 z-10 flex items-center justify-center p-4"
        :style="{ color: textColor || '#000000' }"
    >
        <p class="text-center">{{ content }}</p>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    enabled?: boolean;
    content?: string;
    textColor?: string;
}>();
</script>

