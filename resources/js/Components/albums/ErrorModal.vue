<template>
    <BaseModal :show="show" size="sm" @close="close">
        <template #header>
            <div class="flex items-start">
                <div
                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"
                >
                    <svg
                        class="h-6 w-6 text-red-600"
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
                </div>
                <h3 class="ml-4 text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                    Error
                </h3>
            </div>
        </template>

        <template #body>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ message }}
            </p>
        </template>

        <template #footer>
            <BaseButton variant="danger" @click="close">
                Close
            </BaseButton>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import BaseModal from '@/Components/Base/Modal.vue';
import BaseButton from '@/Components/Base/Button.vue';

const show = ref(false);
const message = ref('');

const showError = (errorMessage: string) => {
    message.value = errorMessage;
    show.value = true;
};

const close = () => {
    show.value = false;
    message.value = '';
};

defineExpose({
    showError
});
</script>
