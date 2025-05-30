import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Mosaic, MosaicItem, MosaicUploadProgress, MosaicConfirmation } from '@/types/mosaic';

export function useMosaic(mosaicId: number) {
    const uploading = ref(false);
    const uploadProgress = ref(0);
    const showConfirmation = ref(false);
    const confirmationTitle = ref('');
    const confirmationMessage = ref('');
    const confirmationAction = ref<(() => void) | null>(null);

    const handleFileUpload = async (event: Event) => {
        const input = event.target as HTMLInputElement;
        if (!input.files?.length) return;

        uploading.value = true;
        uploadProgress.value = 0;

        const formData = new FormData();
        Array.from(input.files).forEach(file => {
            formData.append('media[]', file);
        });

        try {
            await router.post(route('mosaics.media.store', mosaicId), formData, {
                forceFormData: true,
                onProgress: (progress) => {
                    if (progress?.percentage !== undefined) {
                        uploadProgress.value = progress.percentage;
                    }
                },
            });
        } catch (error) {
            console.error('Upload failed:', error);
        } finally {
            uploading.value = false;
            uploadProgress.value = 0;
        }
    };

    const deleteMosaic = async () => {
        try {
            await router.delete(route('mosaics.destroy', mosaicId));
        } catch (error) {
            console.error('Delete failed:', error);
        }
    };

    const deleteItem = async (itemId: number) => {
        try {
            await router.delete(route('mosaics.items.destroy', [mosaicId, itemId]));
        } catch (error) {
            console.error('Delete failed:', error);
        }
    };

    const reorderItems = async (fromId: number, toId: number) => {
        try {
            await router.put(route('mosaics.items.reorder', mosaicId), {
                from_id: fromId,
                to_id: toId,
            });
        } catch (error) {
            console.error('Reorder failed:', error);
        }
    };

    const addItem = async (item: Partial<MosaicItem>) => {
        try {
            const payload = {
                type: item.type,
                properties: JSON.stringify(item.properties),
                order: item.order,
            };
            await router.post(route('mosaics.items.store', mosaicId), payload);
        } catch (error) {
            console.error('Add item failed:', error);
        }
    };

    const updateItem = async (itemId: number, updates: Partial<MosaicItem>) => {
        try {
            const payload = {
                type: updates.type,
                properties: updates.properties ? JSON.stringify(updates.properties) : undefined,
                order: updates.order,
            };
            await router.put(route('mosaics.items.update', [mosaicId, itemId]), payload);
        } catch (error) {
            console.error('Update item failed:', error);
        }
    };

    const showConfirmationDialog = (title: string, message: string, action: () => void) => {
        confirmationTitle.value = title;
        confirmationMessage.value = message;
        confirmationAction.value = action;
        showConfirmation.value = true;
    };

    const confirmAction = () => {
        if (confirmationAction.value) {
            confirmationAction.value();
        }
        showConfirmation.value = false;
    };

    const cancelConfirmation = () => {
        showConfirmation.value = false;
        confirmationAction.value = null;
    };

    return {
        uploading,
        uploadProgress,
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
    };
} 