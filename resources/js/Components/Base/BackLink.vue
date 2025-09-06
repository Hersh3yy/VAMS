<template>
    <Link
        :href="href"
        class="mr-4 inline-flex items-center rounded-full bg-gray-200 px-4 py-2 font-bold text-gray-800 transition-all duration-200 hover:bg-gray-300"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="mr-1 h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
        </svg>
        {{ text || 'Back' }}
    </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    href: string;
    text?: string;
}

defineProps<Props>();
</script>
