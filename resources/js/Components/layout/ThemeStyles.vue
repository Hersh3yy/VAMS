<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { User, PageProps } from '@/types';

const page = usePage<PageProps>();

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
</script>

<template>
    <div :style="userThemeStyle">
        <slot />
    </div>
</template>

<style>
:root {
    --primary-color: #4F46E5;
    --secondary-color: #10B981;
}

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