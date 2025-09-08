<template>
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ title }}</h2>
        <div class="flex space-x-3">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    title: string;
}

defineProps<Props>();
</script>
