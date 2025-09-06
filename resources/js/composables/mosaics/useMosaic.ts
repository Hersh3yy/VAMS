import type { MosaicItem } from '@/types/mosaic';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useMosaic(mosaicId: string) {
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
                onProgress: progress => {
                    if (progress?.percentage !== undefined) {
                        uploadProgress.value = progress.percentage;
                    }
                }
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

    const deleteItem = async (itemId: string) => {
        try {
            await router.delete(route('mosaics.items.destroy', [mosaicId, itemId]), {
                preserveScroll: true,
                preserveState: false,
                only: ['mosaic']
            });
        } catch (error) {
            console.error('Delete failed:', error);
        }
    };

    const reorderItems = async (fromId: string, toId: string) => {
        try {
            await router.patch(
                route('mosaics.items.reorder', mosaicId),
                {
                    from_id: fromId,
                    to_id: toId
                },
                {
                    preserveScroll: true,
                    preserveState: false,
                    only: ['mosaic']
                }
            );
        } catch (error) {
            console.error('Reorder failed:', error);
        }
    };

    const addItem = async (item: Partial<MosaicItem>) => {
        try {
            const payload = {
                type: item.type,
                properties: item.properties || {},
                order: item.order || 0,
                column_index: item.column_index || 0
            } as any;
            await router.post(route('mosaics.items.store', mosaicId), payload, {
                preserveScroll: true,
                preserveState: false,
                only: ['mosaic']
            });
        } catch (error) {
            console.error('Add item failed:', error);
        }
    };

    const updateItem = async (itemId: string, updates: Partial<MosaicItem>) => {
        try {
            const payload = {
                type: updates.type,
                properties: updates.properties || {},
                order: updates.order || 0,
                column_index: updates.column_index || 0
            } as any;
            await router.put(route('mosaics.items.update', [mosaicId, itemId]), payload, {
                preserveScroll: true,
                preserveState: false,
                only: ['mosaic']
            });
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
        cancelConfirmation
    };
}
