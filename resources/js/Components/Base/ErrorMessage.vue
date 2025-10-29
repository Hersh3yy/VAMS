<template>
    <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">
        {{ error }}
    </p>
</template>

<script setup lang="ts">
interface Props {
    error?: string;
}

defineProps<Props>();
</script>
