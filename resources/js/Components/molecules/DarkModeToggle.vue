<template>
    <button
        type="button"
        class="rounded-full border-2 border-transparent p-1.5 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-secondary dark:text-gray-400 dark:hover:text-gray-200"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggleDarkMode"
    >
        <Icon :name="isDark ? 'sun' : 'moon'" size="sm" />
    </button>
</template>

<script setup lang="ts">
import Icon from '@/Components/Base/Icon.vue';
import { useDarkMode } from '@/composables/shared/useDarkMode';

const { isDark, toggleDarkMode } = useDarkMode();
</script>
