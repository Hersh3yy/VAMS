<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div
            class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0"
        >
            <!-- Backdrop -->
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75" />
            </div>

            <!-- Modal positioning -->
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true"
                >&#8203;</span
            >

            <!-- Modal content -->
            <div
                :class="modalClasses"
                class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle"
            >
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    show: boolean;
    size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md'
});

const modalClasses = computed(() => {
    const sizeClasses = {
        sm: 'sm:w-full sm:max-w-sm',
        md: 'sm:w-full sm:max-w-md',
        lg: 'sm:w-full sm:max-w-lg',
        xl: 'sm:w-full sm:max-w-xl',
        '2xl': 'sm:w-full sm:max-w-2xl'
    };

    return `${sizeClasses[props.size]} sm:p-6`;
});
</script>
