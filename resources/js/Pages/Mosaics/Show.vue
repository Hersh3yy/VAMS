<template>
    <div class="min-h-screen bg-gray-100">
        <MosaicHeader
            :mosaic="mosaic"
            @edit="showEditModal = true"
            @add-item="showItemEditor = true"
            @delete="handleDeleteMosaic"
        />

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <MosaicGrid
                :items="mosaic.items"
                :settings="settings"
                @item-click="handleItemClick"
                @item-delete="handleItemDelete"
                @item-reorder="handleItemReorder"
            />
        </div>

        <!-- Item Editor Modal -->
        <MosaicItemEditor
            v-if="showItemEditor"
            :item="selectedItem"
            :albums="albums"
            @close="closeItemEditor"
            @save="handleSaveItem"
            @delete="handleDeleteItem"
        />

        <!-- Edit Mosaic Modal -->
        <div v-if="showEditModal" class="fixed z-10 inset-0 overflow-y-auto">
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
                            @click="showEditModal = false"
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
                                        v-model="editForm.title"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea
                                        v-model="editForm.description"
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
                                                v-model="editForm.settings.grid_columns"
                                                min="1"
                                                max="6"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            >
                                        </div>

                                        <div>
                                            <label class="block text-sm text-gray-700">Gap (px)</label>
                                            <input
                                                type="number"
                                                v-model="editForm.settings.gap"
                                                min="0"
                                                max="32"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            >
                                        </div>

                                        <div>
                                            <label class="block text-sm text-gray-700">Padding (px)</label>
                                            <input
                                                type="number"
                                                v-model="editForm.settings.padding"
                                                min="0"
                                                max="32"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            >
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                type="checkbox"
                                                v-model="editForm.settings.show_titles"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            >
                                            <label class="ml-2 block text-sm text-gray-900">Show Titles</label>
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                type="checkbox"
                                                v-model="editForm.settings.show_captions"
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
                            @click="handleSaveMosaic"
                        >
                            Save Changes
                        </button>
                        <button
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                            @click="showEditModal = false"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Mosaic, MosaicItem, MosaicDisplaySettings } from '@/types/mosaic';
import MosaicHeader from '@/Components/mosaics/MosaicHeader.vue';
import MosaicGrid from '@/Components/mosaics/MosaicGrid.vue';
import MosaicItemEditor from '@/Components/mosaics/MosaicItemEditor.vue';
import { useMosaic } from '@/composables/mosaics/useMosaic';

const props = defineProps<{
    mosaic: Mosaic;
    albums: Array<{
        id: number;
        title: string;
        cover_image_path?: string;
    }>;
}>();

const {
    showConfirmation,
    confirmationTitle,
    confirmationMessage,
    handleFileUpload,
    deleteMosaic,
    deleteItem,
    reorderItems,
    addItem,
    updateItem,
    showConfirmationDialog,
    confirmAction,
    cancelConfirmation,
} = useMosaic(props.mosaic.id);

const showItemEditor = ref(false);
const showEditModal = ref(false);
const selectedItem = ref<MosaicItem | undefined>();

const settings = reactive<MosaicDisplaySettings>({
    grid_columns: 3,
    gap: 16,
    padding: 16,
    show_titles: true,
    show_captions: true,
});

const editForm = reactive({
    title: props.mosaic.title,
    description: props.mosaic.description,
    settings: { ...settings },
});

const handleItemClick = (item: MosaicItem) => {
    selectedItem.value = item;
    showItemEditor.value = true;
};

const handleItemDelete = (item: MosaicItem) => {
    showConfirmationDialog(
        'Delete Item',
        'Are you sure you want to delete this item? This action cannot be undone.',
        () => deleteItem(item.id)
    );
};

const handleItemReorder = (fromId: number, toId: number) => {
    reorderItems(fromId, toId);
};

const closeItemEditor = () => {
    showItemEditor.value = false;
    selectedItem.value = undefined;
};

const handleSaveItem = (item: Partial<MosaicItem>) => {
    if (selectedItem.value) {
        updateItem(selectedItem.value.id, item);
    } else {
        addItem(item);
    }
    closeItemEditor();
};

const handleDeleteItem = () => {
    if (selectedItem.value) {
        handleItemDelete(selectedItem.value);
    }
    closeItemEditor();
};

const handleDeleteMosaic = () => {
    showConfirmationDialog(
        'Delete Mosaic',
        'Are you sure you want to delete this mosaic? This action cannot be undone.',
        () => {
            deleteMosaic();
            router.visit(route('mosaics.index'));
        }
    );
};

const handleSaveMosaic = async () => {
    try {
        await router.put(route('mosaics.update', props.mosaic.id), {
            title: editForm.title,
            description: editForm.description,
            settings: editForm.settings,
        });
        showEditModal.value = false;
    } catch (error) {
        console.error('Failed to update mosaic:', error);
    }
};
</script> 