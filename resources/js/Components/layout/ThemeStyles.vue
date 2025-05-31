<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { User, PageProps } from '@/types';

const page = usePage<PageProps>();
const isLoading = ref(false);

const userThemeStyle = computed(() => {
    const user = page.props.auth?.user as User;
    if (!user) return { '--primary-color': '#4F46E5', '--secondary-color': '#10B981' };
    
    const settings = user.album_display_settings || {};
    const mainColor = settings.main_color || '#4F46E5'; // Default indigo
    const secondaryColor = settings.secondary_color || '#10B981'; // Default emerald
    
    return {
        '--primary-color': mainColor,
        '--secondary-color': secondaryColor,
    };
});

// Handle dark mode
onMounted(() => {
    // Check for saved theme preference or use system preference
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});

// Watch for system theme changes
watch(() => window.matchMedia('(prefers-color-scheme: dark)').matches, (isDark) => {
    if (!localStorage.getItem('theme')) {
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
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
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
            <div class="h-32 w-32 animate-spin rounded-full border-b-2 border-t-2 border-indigo-500"></div>
        </div>
        <slot />
    </div>
</template>

<style>
:root {
    --primary-color: #4F46E5;
    --secondary-color: #10B981;
}

/* Base text colors */
.text-base {
    @apply text-gray-900 dark:text-gray-100;
}

.text-muted {
    @apply text-gray-600 dark:text-gray-400;
}

/* Background colors */
.bg-base {
    @apply bg-white dark:bg-gray-800;
}

.bg-muted {
    @apply bg-gray-100 dark:bg-gray-900;
}

/* Border colors */
.border-base {
    @apply border-gray-200 dark:border-gray-700;
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
    background-color: var(--primary-color) !important;
}

.hover\:bg-blue-700:hover {
    background-color: var(--primary-color) !important;
    filter: brightness(90%);
}

.bg-green-500 {
    background-color: var(--secondary-color) !important;
}

.hover\:bg-green-700:hover {
    background-color: var(--secondary-color) !important;
    filter: brightness(90%);
}
</style> 