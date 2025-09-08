<template>
    <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
        <h3 class="mb-2 text-lg font-semibold text-gray-700">{{ title }}</h3>
        <p class="text-3xl font-bold" :class="valueClass">
            {{ value }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    title: string;
    value: string | number;
    variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'purple';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary'
});

const valueClass = computed(() => {
    const classes = {
        primary: 'text-primary',
        secondary: 'text-secondary', 
        success: 'text-green-600',
        warning: 'text-yellow-600',
        danger: 'text-red-600',
        purple: 'text-purple-600'
    };
    return classes[props.variant];
});
</script>