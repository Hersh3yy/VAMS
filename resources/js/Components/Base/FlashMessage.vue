<template>
    <div
        v-if="message"
        :class="[
            'relative rounded border px-4 py-3',
            type === 'success' ? 'border-green-400 bg-green-100 text-green-700' : '',
            type === 'error' ? 'border-red-400 bg-red-100 text-red-700' : '',
            type === 'warning' ? 'border-yellow-400 bg-yellow-100 text-yellow-700' : '',
            type === 'info' ? 'border-blue-400 bg-blue-100 text-blue-700' : ''
        ]"
        role="alert"
    >
        <span class="block sm:inline">{{ message }}</span>
    </div>
</template>

<script setup lang="ts">
interface Props {
    message?: string;
    type?: 'success' | 'error' | 'warning' | 'info';
}

defineProps<Props>();
</script>
