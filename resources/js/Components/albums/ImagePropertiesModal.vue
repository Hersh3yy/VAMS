<template>
    <Modal :show="show" @close="closeModal" maxWidth="lg">
        <div class="bg-gray-800 p-6 text-white">
            <h3 class="mb-4 text-xl font-bold">Image Properties</h3>

            <div v-if="localItem">
                <!-- Link URL -->
                <div class="mb-4">
                    <label for="linkUrl" class="mb-1 block text-sm font-medium text-gray-300"
                        >Link URL (optional)</label
                    >
                    <input
                        type="text"
                        id="linkUrl"
                        v-model="editableProperties.linkUrl"
                        placeholder="e.g., /my-page or https://example.com"
                        class="w-full rounded border border-gray-600 bg-gray-700 p-2 text-white focus:border-blue-500 focus:ring-blue-500"
                    />
                    <p class="mt-1 text-xs text-gray-400">
                        If internal (e.g. /about), it will use frontend routing. Full URLs for
                        external sites.
                    </p>
                </div>

                <!-- Text Overlay -->
                <div class="mb-4">
                    <label for="overlayText" class="mb-1 block text-sm font-medium text-gray-300"
                        >Text Overlay (optional)</label
                    >
                    <input
                        type="text"
                        id="overlayText"
                        v-model="editableProperties.overlayText"
                        placeholder="Text to display on image"
                        class="w-full rounded border border-gray-600 bg-gray-700 p-2 text-white focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Additional Overlay Config (Example: Text Color) -->
                <div v-if="editableProperties.overlayText" class="mb-4">
                    <label for="overlayColor" class="mb-1 block text-sm font-medium text-gray-300"
                        >Overlay Text Color</label
                    >
                    <input
                        type="color"
                        id="overlayColor"
                        v-model="editableProperties.overlayConfig.color"
                        class="h-10 w-full rounded border border-gray-600 bg-gray-700 p-1 text-white"
                    />
                </div>
            </div>
            <div v-else class="py-4 text-center">
                <p>No image item selected.</p>
            </div>

            <div class="mt-6 flex justify-end gap-4">
                <button
                    @click="closeModal"
                    class="rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-500"
                >
                    Cancel
                </button>
                <button @click="saveProperties" class="btn-primary" :disabled="!localItem">
                    Save Changes
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/Components/general/Modal.vue'; // Assuming Modal is in this path
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
