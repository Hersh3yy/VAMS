<template>
    <BaseModal :show="show" size="lg" closeable @close="closeModal">
        <template #header>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Image Properties</h3>
        </template>

        <template #body>
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
            <div v-else class="py-4 text-center text-gray-500 dark:text-gray-400">
                <p>No image item selected.</p>
            </div>
        </template>

        <template #footer>
            <BaseButton variant="primary" @click="saveProperties">
                Save Changes
            </BaseButton>
            <BaseButton variant="secondary" @click="closeModal">
                Cancel
            </BaseButton>
        </template>
    </BaseModal>
</template>

<script setup>
import ImagePropertyField from '@/Components/molecules/ImagePropertyField.vue';
import BaseModal from '@/Components/Base/Modal.vue';
import BaseButton from '@/Components/Base/Button.vue';
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
