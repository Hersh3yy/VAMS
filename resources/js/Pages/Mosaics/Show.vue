<template>
    <Head :title="Mosaic.title" />

    <AuthenticatedLayout>
        <template #header>
            <MosaicHeader
                :mosaic="Mosaic"
                @edit="showEditModal = true"
                @add-item="showItemEditor = true"
                @delete="handleDeleteMosaic"
            />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <MosaicGrid
                        :items="Mosaic.items"
                        :settings="settings"
                        @item-click="handleItemClick"
                        @item-delete="handleItemDelete"
                        @reorder="handleItemReorder"
                        @add-item="handleAddItem"
                        draggable="true"
                    />
                </div>
            </div>
        </div>

        <!-- Item Editor Modal -->
        <SimpleMosaicItemEditor
            :show="showItemEditor"
            :item="selectedItem"
            :albums="albums"
            :mosaic-id="Mosaic.id"
            @close="closeItemEditor"
            @save="handleSaveItem"
            @delete="handleDeleteItem"
            @update="handleItemUpdate"
        />

        <!-- Edit Mosaic Modal -->
        <MosaicEditModal
            :show="showEditModal"
            :mosaic="Mosaic"
            @close="showEditModal = false"
            @save="handleSaveMosaic"
        />

        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            :show="showConfirmation"
            :title="confirmationTitle"
            :message="confirmationMessage"
            confirm-text="Delete"
            cancel-text="Cancel"
            @confirm="confirmAction"
            @cancel="cancelConfirmation"
        />
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import ConfirmationDialog from '@/Components/molecules/ConfirmationDialog.vue';
import MosaicEditModal from '@/Components/mosaics/MosaicEditModal.vue';
import MosaicGrid from '@/Components/mosaics/MosaicGrid.vue';
import MosaicHeader from '@/Components/mosaics/MosaicHeader.vue';
import SimpleMosaicItemEditor from '@/Components/mosaics/SimpleMosaicItemEditor.vue';
import { useMosaic } from '@/composables/mosaics/useMosaic';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Album } from '@/types/album';
import type { Mosaic, MosaicDisplaySettings, MosaicItem } from '@/types/mosaic';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps<{
    Mosaic: Mosaic;
    albums: Album[];
}>();

// Add null check for mosaic
if (!props.Mosaic) {
    throw new Error('Mosaic data is required');
}

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
    cancelConfirmation
} = useMosaic(String(props.Mosaic.id));

const showItemEditor = ref(false);
const showEditModal = ref(false);
const selectedItem = ref<MosaicItem | null>(null);

const settings = reactive<MosaicDisplaySettings>({
    grid_columns: 3,
    gap: 16,
    padding: 16,
    show_titles: true,
    show_captions: true
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

const handleItemReorder = (fromId: string, toId: string) => {
    reorderItems(fromId, toId);
};

const handleAddItem = (columnIndex: number) => {
    // Create a new item with the specified column index
    const newItem: MosaicItem = {
        id: '',
        type: 'album',
        column_index: columnIndex,
        order: 0,
        properties: {}
    };
    selectedItem.value = newItem;
    showItemEditor.value = true;
};

const closeItemEditor = () => {
    showItemEditor.value = false;
    selectedItem.value = null;
};

const handleItemUpdate = () => {
    // Force reactivity update - this will be called when items are updated
    router.reload({ only: ['mosaic'] });
};

const handleSaveItem = (item: Partial<MosaicItem>) => {
    if (selectedItem.value && selectedItem.value.id && selectedItem.value.id !== '') {
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

const handleSaveMosaic = async (data: {
    title: string;
    description: string;
    settings: MosaicDisplaySettings;
}) => {
    try {
        await router.patch(route('mosaics.update', props.Mosaic.id), {
            title: data.title,
            description: data.description,
            display_settings: { ...data.settings }
        });
        showEditModal.value = false;
    } catch (error) {
        console.error('Failed to update mosaic:', error);
    }
};
</script>
