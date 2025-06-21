<template>
    <div v-if="show" class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button
                        type="button"
                        class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="$emit('close')"
                    >
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Edit Mosaic
                        </h3>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input
                                    type="text"
                                    v-model="formData.title"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea
                                    v-model="formData.description"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Display Settings</label>
                                <div class="mt-2 space-y-4">
                                    <div>
                                        <label class="block text-sm text-gray-700">Grid Columns</label>
                                        <input
                                            type="number"
                                            v-model="formData.settings.grid_columns"
                                            min="1"
                                            max="6"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-700">Gap (px)</label>
                                        <input
                                            type="number"
                                            v-model="formData.settings.gap"
                                            min="0"
                                            max="32"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-700">Padding (px)</label>
                                        <input
                                            type="number"
                                            v-model="formData.settings.padding"
                                            min="0"
                                            max="32"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        >
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="formData.settings.show_titles"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                        >
                                        <label class="ml-2 block text-sm text-gray-900">Show Titles</label>
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="formData.settings.show_captions"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                        >
                                        <label class="ml-2 block text-sm text-gray-900">Show Captions</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="handleSave"
                    >
                        Save Changes
                    </button>
                    <button
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import type { Mosaic, MosaicDisplaySettings } from '@/types/mosaic';

const props = defineProps<{
    show: boolean;
    mosaic: Mosaic;
}>();

const emit = defineEmits<{
    close: [];
    save: [data: { title: string; description: string; settings: MosaicDisplaySettings }];
}>();

const formData = reactive({
    title: props.mosaic.title || '',
    description: props.mosaic.description || '',
    settings: {
        grid_columns: 3,
        gap: 16,
        padding: 16,
        show_titles: true,
        show_captions: true,
    } as MosaicDisplaySettings,
});

// Watch for changes in the mosaic prop to update form data when modal opens
watch(() => props.mosaic, (newMosaic) => {
    if (newMosaic) {
        formData.title = newMosaic.title || '';
        formData.description = newMosaic.description || '';
        // Update settings if they exist on the mosaic, otherwise use defaults
        if (newMosaic.display_settings) {
            Object.assign(formData.settings, newMosaic.display_settings);
        }
    }
}, { immediate: true });

const handleSave = () => {
    emit('save', {
        title: formData.title,
        description: formData.description,
        settings: formData.settings,
    });
};
</script> 