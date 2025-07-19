<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div
            class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0"
        >
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75" />
            </div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true"
                >&#8203;</span
            >

            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle"
            >
                <div class="absolute right-0 top-0 pr-4 pt-4">
                    <button
                        type="button"
                        class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="$emit('close')"
                    >
                        <span class="sr-only">Close</span>
                        <svg
                            class="h-6 w-6"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    <div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Edit Mosaic</h3>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input
                                    type="text"
                                    v-model="formData.title"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700"
                                    >Description</label
                                >
                                <textarea
                                    v-model="formData.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700"
                                    >Display Settings</label
                                >
                                <div class="mt-2 space-y-4">
                                    <div>
                                        <label class="block text-sm text-gray-700"
                                            >Grid Columns</label
                                        >
                                        <input
                                            type="number"
                                            v-model="formData.settings.grid_columns"
                                            min="1"
                                            max="6"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-700">Gap (px)</label>
                                        <input
                                            type="number"
                                            v-model="formData.settings.gap"
                                            min="0"
                                            max="32"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-700"
                                            >Padding (px)</label
                                        >
                                        <input
                                            type="number"
                                            v-model="formData.settings.padding"
                                            min="0"
                                            max="32"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="formData.settings.show_titles"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <label class="ml-2 block text-sm text-gray-900"
                                            >Show Titles</label
                                        >
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="formData.settings.show_captions"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <label class="ml-2 block text-sm text-gray-900"
                                            >Show Captions</label
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button
                        type="button"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="handleSave"
                    >
                        Save Changes
                    </button>
                    <button
                        type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm"
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
import type { Mosaic, MosaicDisplaySettings } from '@/types/mosaic';
import { reactive, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    mosaic: Mosaic;
}>();

const emit = defineEmits<{
    close: [];
    save: [
        data: {
            title: string;
            description: string;
            settings: MosaicDisplaySettings;
        }
    ];
}>();

const formData = reactive({
    title: props.mosaic.title || '',
    description: props.mosaic.description || '',
    settings: {
        grid_columns: 3,
        gap: 16,
        padding: 16,
        show_titles: true,
        show_captions: true
    } as MosaicDisplaySettings
});

// Watch for changes in the mosaic prop to update form data when modal opens
watch(
    () => props.mosaic,
    newMosaic => {
        if (newMosaic) {
            formData.title = newMosaic.title || '';
            formData.description = newMosaic.description || '';
            // Update settings if they exist on the mosaic, otherwise use defaults
            if (newMosaic.display_settings) {
                Object.assign(formData.settings, newMosaic.display_settings);
            }
        }
    },
    { immediate: true }
);

const handleSave = () => {
    emit('save', {
        title: formData.title,
        description: formData.description,
        settings: formData.settings
    });
};
</script>
