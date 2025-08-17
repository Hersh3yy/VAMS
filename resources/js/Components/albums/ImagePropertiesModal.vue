<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div
            class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0"
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
                <div class="flex items-center justify-between border-b p-4">
                    <h3 class="text-lg font-medium">Image Properties</h3>
                    <button
                        type="button"
                        class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="closeModal"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div v-if="localItem" class="space-y-4">
                        <!-- Link URL -->
                        <ImagePropertyField
                            v-model="editableProperties.linkUrl"
                            label="Link URL (optional)"
                            id="linkUrl"
                            type="text"
                            placeholder="e.g., /my-page or https://example.com"
                            help-text="If internal (e.g. /about), it will use frontend routing. Full URLs for external sites."
                        />

                        <!-- Text Overlay -->
                        <ImagePropertyField
                            v-model="editableProperties.overlayText"
                            label="Text Overlay (optional)"
                            id="overlayText"
                            type="text"
                            placeholder="Text to display on image"
                        />

                        <!-- Text Color -->
                        <div v-if="editableProperties.overlayText" class="mb-4">
                            <label
                                for="overlayColor"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Overlay Text Color
                            </label>
                            <input
                                type="color"
                                id="overlayColor"
                                v-model="editableProperties.overlayConfig.color"
                                class="mt-1 h-10 w-full rounded border border-gray-300 bg-white p-1"
                            >
                        </div>
                    </div>
                    <div v-else class="py-4 text-center">
                        <p>No image item selected.</p>
                    </div>
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button
                        type="button"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="saveProperties"
                    >
                        Save Changes
                    </button>
                    <button
                        type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm"
                        @click="closeModal"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import ImagePropertyField from '@/Components/Composite/ImagePropertyField.vue';
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    item: Object // The mosaic item of type 'image'
});

const emit = defineEmits(['close', 'update:properties']);

const localItem = ref(null);
const editableProperties = reactive({
    linkUrl: '',
    overlayText: '',
    overlayConfig: {
        color: '#FFFFFF' // Default white
        // We can add more later: fontSize, position, etc.
    }
});

watch(
    () => props.item,
    newItem => {
        localItem.value = newItem;
        if (newItem && newItem.properties) {
            let currentProps;
            try {
                currentProps =
                    typeof newItem.properties === 'string'
                        ? JSON.parse(newItem.properties)
                        : newItem.properties;
            } catch (e) {
                // Silently handle malformed properties
                currentProps = {};
            }
            editableProperties.linkUrl = currentProps.linkUrl || '';
            editableProperties.overlayText = currentProps.overlayText || '';
            editableProperties.overlayConfig = {
                ...{ color: '#FFFFFF' }, // Ensure default
                ...(currentProps.overlayConfig || {})
            };
        } else {
            // Reset if no item or no properties
            editableProperties.linkUrl = '';
            editableProperties.overlayText = '';
            editableProperties.overlayConfig = { color: '#FFFFFF' };
        }
    },
    { immediate: true, deep: true }
);

const closeModal = () => {
    emit('close');
};

const saveProperties = () => {
    if (!localItem.value) return;

    // Construct the properties to be saved, merging with existing ones
    // to not lose other properties like path, webp_path, position etc.
    let existingProps = {};
    try {
        existingProps =
            typeof localItem.value.properties === 'string'
                ? JSON.parse(localItem.value.properties)
                : localItem.value.properties || {};
    } catch (e) {
        // Silently handle malformed existing properties
        existingProps = {};
    }

    const updatedProps = {
        ...existingProps,
        linkUrl: editableProperties.linkUrl,
        overlayText: editableProperties.overlayText,
        overlayConfig: editableProperties.overlayConfig
    };

    emit('update:properties', {
        itemId: localItem.value.id,
        properties: updatedProps
    });
    closeModal();
};
</script>

<style scoped>
/* Add any specific styles for this modal if needed */
input[type='color']::-webkit-color-swatch-wrapper {
    padding: 0;
}
input[type='color']::-webkit-color-swatch {
    border: none;
    border-radius: 0.25rem; /* Match rounded class */
}
</style>
