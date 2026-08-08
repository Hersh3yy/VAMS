<template>
    <div v-if="show" class="relative z-50" :aria-labelledby="titleId" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal content -->
                <div
                    ref="panelRef"
                    :class="modalClasses"
                    class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:p-6 dark:bg-gray-800"
                >
                    <!-- Header -->
                    <div v-if="hasHeaderSlot" class="mb-4">
                        <div class="flex items-center justify-between">
                            <div :id="titleId" class="flex-1">
                                <slot name="header" />
                            </div>
                            <button
                                v-if="closeable"
                                type="button"
                                class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:hover:text-gray-200"
                                @click="$emit('close')"
                            >
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="mt-3 sm:mt-0">
                        <slot name="body">
                            <slot />
                        </slot>
                    </div>

                    <!-- Footer -->
                    <div v-if="hasFooterSlot" class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, useSlots, useTemplateRef, toRef } from 'vue';
import { useFocusTrap } from '@/composables/shared/useFocusTrap';

interface Props {
    show: boolean;
    size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '4xl';
    closeable?: boolean;
    titleId?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    closeable: false,
    titleId: 'modal-title'
});

const emit = defineEmits<{
    close: [];
}>();

const slots = useSlots();

const hasHeaderSlot = computed(() => !!slots.header);
const hasFooterSlot = computed(() => !!slots.footer);

const panelRef = useTemplateRef<HTMLElement>('panelRef');

useFocusTrap(panelRef, toRef(props, 'show'), () => emit('close'));

const modalClasses = computed(() => {
    const sizeClasses = {
        sm: 'sm:w-full sm:max-w-sm',
        md: 'sm:w-full sm:max-w-md',
        lg: 'sm:w-full sm:max-w-lg',
        xl: 'sm:w-full sm:max-w-xl',
        '2xl': 'sm:w-full sm:max-w-2xl',
        '4xl': 'sm:w-full sm:max-w-4xl'
    };

    return sizeClasses[props.size] || sizeClasses.md;
});
</script>
