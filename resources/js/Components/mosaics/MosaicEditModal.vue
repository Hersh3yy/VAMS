<template>
    <BaseModal :show="show" size="lg" closeable @close="$emit('close')">
        <template #header>
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">Edit Mosaic</h3>
        </template>

        <template #body>
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
        </template>

        <template #footer>
            <BaseButton variant="primary" @click="handleSave">
                Save Changes
            </BaseButton>
            <BaseButton variant="secondary" @click="$emit('close')">
                Cancel
            </BaseButton>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import type { Mosaic, MosaicDisplaySettings } from '@/types/mosaic';
import BaseModal from '@/Components/Base/Modal.vue';
import BaseButton from '@/Components/Base/Button.vue';
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
