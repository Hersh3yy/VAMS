<template>
    <div class="min-h-screen bg-gray-100">
        <MosaicHeader
            :mosaic="mosaic"
            @edit="showEditModal = true"
            @add-item="showItemEditor = true"
            @delete="handleDeleteMosaic"
        />

        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
            <MosaicGrid
                :items="mosaic.items"
                :settings="settings"
                @item-click="handleItemClick"
                @item-delete="handleItemDelete"
                @reorder="handleItemReorder"
                draggable="true"
            />
        </div>

        <!-- Item Editor Modal -->
        <MosaicItemWizard
            v-if="showItemEditor"
            :show="showItemEditor"
            :is-editing="!!selectedItem"
            :item="selectedItem"
            :albums="albums"
            @close="closeItemEditor"
            @save="handleSaveItem"
        />

        <!-- Edit Mosaic Modal -->
        <MosaicEditModal
            :show="showEditModal"
            :mosaic="mosaic"
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
    </div>
</template>

<script setup lang="ts">
import MosaicEditModal from '@/Components/mosaics/MosaicEditModal.vue';
import MosaicGrid from '@/Components/mosaics/MosaicGrid.vue';
import MosaicHeader from '@/Components/mosaics/MosaicHeader.vue';
import MosaicItemWizard from '@/Components/mosaics/MosaicItemWizard.vue';
import ConfirmationDialog from '@/Components/shared/ConfirmationDialog.vue';
import { useMosaic } from '@/composables/mosaics/useMosaic';
import type { Album } from '@/types/album';
import type { Mosaic, MosaicDisplaySettings, MosaicItem } from '@/types/mosaic';
import { router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps<{
    mosaic: Mosaic;
    albums: Album[];
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
} = useMosaic(String(props.mosaic.id));

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

const handleItemClick = (item: MosaicItem) => {
    selectedItem.value = item;
    showItemEditor.value = true;
};

const handleItemDelete = (item: MosaicItem) => {
    showConfirmationDialog(
        'Delete Item',
        'Are you sure you want to delete this item? This action cannot be undone.',
        () => deleteItem(item.id),
    );
};

const handleItemReorder = (fromId: string, toId: string) => {
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
        },
    );
};

const handleSaveMosaic = async (data: {
    title: string;
    description: string;
    settings: MosaicDisplaySettings;
}) => {
    try {
        await router.put(route('mosaics.update', props.mosaic.id), {
            title: data.title,
            description: data.description,
            display_settings: { ...data.settings },
        });
        showEditModal.value = false;
    } catch (error) {
        console.error('Failed to update mosaic:', error);
    }
};
</script>
