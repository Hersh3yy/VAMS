<template>
    <label 
        :for="forId" 
        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
    >
        {{ text }}
        <span v-if="required" class="text-red-500">*</span>
    </label>
</template>

<script setup lang="ts">
interface Props {
    text: string;
    forId?: string;
    required?: boolean;
}

defineProps<Props>();
</script>
