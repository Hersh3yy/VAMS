<template>
    <div class="bg-white shadow">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl">
                        {{ mosaic.title }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ mosaic.description }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2"
                        @click="$emit('edit')"
                    >
                        Edit Details
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        @click="confirmDelete"
                    >
                        Delete Mosaic
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <ConfirmationDialog
        :show="showConfirmation"
        title="Delete Mosaic"
        message="Are you sure you want to delete this mosaic? This action cannot be undone."
        confirm-text="Delete"
        cancel-text="Cancel"
        @confirm="handleDelete"
        @cancel="showConfirmation = false"
    />
</template>

<script setup lang="ts">
import ConfirmationDialog from '@/Components/molecules/ConfirmationDialog.vue';
import type { Mosaic } from '@/types/mosaic';
import { ref } from 'vue';

const props = defineProps<{
    mosaic: Mosaic;
}>();

const emit = defineEmits<{
    (e: 'edit'): void;
    (e: 'add-item'): void;
    (e: 'delete'): void;
}>();

const showConfirmation = ref(false);

const confirmDelete = () => {
    showConfirmation.value = true;
};

const handleDelete = () => {
    emit('delete');
    showConfirmation.value = false;
};
</script>
