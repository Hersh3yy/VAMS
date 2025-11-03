<template>
    <div class="mt-2 rounded-md border border-red-300 bg-red-50 p-2">
        <div class="flex items-start">
            <svg
                class="mr-2 mt-0.5 h-5 w-5 flex-shrink-0 text-red-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-red-800">
                    {{ error || 'Upload failed. Please try again.' }}
                </p>
                <div class="mt-2 flex gap-2">
                    <button
                        @click="$emit('retry')"
                        class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    >
                        Retry Upload
                    </button>
                    <button
                        @click="$emit('remove')"
                        class="rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    >
                        Remove
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    error?: string;
}>();

defineEmits<{
    (e: 'retry'): void;
    (e: 'remove'): void;
}>();
</script>

