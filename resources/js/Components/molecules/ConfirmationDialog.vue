<template>
    <BaseModal :show="show" size="sm" @close="$emit('cancel')">
        <template #header>
            <div class="flex items-start">
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg
                        class="h-6 w-6 text-red-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </div>
                <h3 class="ml-4 text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                    {{ title }}
                </h3>
            </div>
        </template>

        <template #body>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ message }}
            </p>
        </template>

        <template #footer>
            <BaseButton variant="danger" @click="$emit('confirm')">
                {{ confirmText }}
            </BaseButton>
            <BaseButton variant="secondary" @click="$emit('cancel')">
                {{ cancelText }}
            </BaseButton>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import BaseModal from '@/Components/Base/Modal.vue'
import BaseButton from '@/Components/Base/Button.vue'

defineProps<{
    show: boolean;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
}>();

defineEmits<{
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();
</script>
