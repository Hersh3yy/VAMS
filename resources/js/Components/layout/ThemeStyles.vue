<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { User, PageProps } from '@/types';

const page = usePage<PageProps>();
const isLoading = ref(false);

const userThemeStyle = computed(() => {
    const user = page.props.auth?.user as User;
    if (!user) return { '--primary-color': '#000000', '--secondary-color': '#EAB308' };
    
    const settings = user.album_display_settings || {};
    const mainColor = settings.main_color || '#000000'; // Default black
    const secondaryColor = settings.secondary_color || '#EAB308'; // Default gold
    
    return {
        '--primary-color': mainColor,
        '--secondary-color': secondaryColor,
    };
});

// Handle dark mode - force black and gold theme
onMounted(() => {
    document.documentElement.classList.add('dark');
});

// Watch for theme changes
watch(() => userThemeStyle.value, () => {
    isLoading.value = true;
    // Small delay to allow the DOM to update
    setTimeout(() => {
        isLoading.value = false;
    }, 100);
});
</script>

<template>
    <div :style="userThemeStyle" class="relative">
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="h-32 w-32 animate-spin rounded-full border-b-2 border-t-2 border-yellow-500"></div>
        </div>
        <slot />
    </div>
</template>

<style>
:root {
    --primary-color: #000000;
    --secondary-color: #EAB308;
}

/* Base text colors - ensure visibility */
.text-gray-900 {
    @apply text-gray-900 dark:text-yellow-100 !important;
}

.text-gray-800 {
    @apply text-gray-800 dark:text-yellow-200 !important;
}

.text-gray-700 {
    @apply text-gray-700 dark:text-yellow-300 !important;
}

.text-gray-600 {
    @apply text-gray-600 dark:text-yellow-400 !important;
}

.text-gray-500 {
    @apply text-gray-500 dark:text-yellow-500 !important;
}

/* Background colors */
.bg-white {
    @apply bg-white dark:bg-gray-900 !important;
}

.bg-gray-100 {
    @apply bg-gray-100 dark:bg-black !important;
}

.bg-gray-50 {
    @apply bg-gray-50 dark:bg-gray-900 !important;
}

/* Border colors */
.border-gray-200 {
    @apply border-gray-200 dark:border-yellow-600 !important;
}

.border-gray-300 {
    @apply border-gray-300 dark:border-yellow-500 !important;
}

/* Component-specific colors */
.bg-primary {
    background-color: var(--primary-color) !important;
}

.text-primary {
    color: var(--primary-color) !important;
}

.border-primary {
    border-color: var(--primary-color) !important;
}

.bg-secondary {
    background-color: var(--secondary-color) !important;
}

.text-secondary {
    color: var(--secondary-color) !important;
}

.border-secondary {
    border-color: var(--secondary-color) !important;
}

/* Override default button styling */
.bg-blue-500 {
    background-color: var(--secondary-color) !important;
}

.hover\:bg-blue-700:hover {
    background-color: var(--secondary-color) !important;
    filter: brightness(90%);
}

.bg-blue-600 {
    background-color: var(--secondary-color) !important;
}

.hover\:bg-blue-600:hover {
    background-color: var(--secondary-color) !important;
    filter: brightness(90%);
}

.bg-green-500 {
    background-color: var(--secondary-color) !important;
}

.hover\:bg-green-700:hover {
    background-color: var(--secondary-color) !important;
    filter: brightness(90%);
}

/* Focus rings */
.focus\:ring-blue-500:focus {
    --tw-ring-color: var(--secondary-color) !important;
}

.focus\:border-blue-500:focus {
    border-color: var(--secondary-color) !important;
}

/* Indigo to gold conversion */
.bg-indigo-600 {
    background-color: var(--secondary-color) !important;
}

.hover\:bg-indigo-500:hover {
    background-color: var(--secondary-color) !important;
    filter: brightness(110%);
}

.text-indigo-600 {
    color: var(--secondary-color) !important;
}

.border-indigo-400 {
    border-color: var(--secondary-color) !important;
}

.focus\:ring-indigo-500:focus {
    --tw-ring-color: var(--secondary-color) !important;
}
</style> 