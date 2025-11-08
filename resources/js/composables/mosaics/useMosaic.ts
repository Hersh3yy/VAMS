import { processImagesForUpload } from '@/utils/imageConverter';
import type { MosaicItem } from '@/types/mosaic';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useMosaic(mosaicId: string) {
    const uploading = ref(false);
    const uploadProgress = ref(0);
    const processingImages = ref(false);
    const showConfirmation = ref(false);
    const confirmationTitle = ref('');
    const confirmationMessage = ref('');
    const confirmationAction = ref<(() => void) | null>(null);

    const handleFileUpload = async (event: Event) => {
        const input = event.target as HTMLInputElement;
        if (!input.files?.length) return;

        const files = Array.from(input.files);
        const MAX_FILE_SIZE = 1.99 * 1024 * 1024; // 1.99MB in bytes

        // Show processing state
        processingImages.value = true;

        try {
            // Process images: convert to WebP and compress to under 1.99MB
            const processedFiles = await processImagesForUpload(
                files,
                MAX_FILE_SIZE,
                (processed, total) => {
                    console.log(`Processing images: ${processed}/${total}`);
                }
            );

            if (processedFiles.length === 0) {
                console.error('No images could be processed');
                return;
            }

            // Log conversion stats
            const originalTotal = files.reduce((sum, f) => sum + f.size, 0);
            const processedTotal = processedFiles.reduce((sum, f) => sum + f.size, 0);
            const savings = ((1 - processedTotal / originalTotal) * 100).toFixed(1);
            console.log(
                `Image processing complete: ${files.length} files, ${(originalTotal / 1024 / 1024).toFixed(2)}MB → ${(processedTotal / 1024 / 1024).toFixed(2)}MB (${savings}% reduction)`
            );

            uploading.value = true;
            uploadProgress.value = 0;

            const formData = new FormData();
            processedFiles.forEach(file => {
                formData.append('media[]', file);
            });

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
            processingImages.value = false;

            // Clear the file input
            if (input) {
                input.value = '';
            }
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
            await router.delete(route('mosaics.items.destroy', [mosaicId, itemId]));
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
                    preserveScroll: true
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
            await router.post(route('mosaics.items.store', mosaicId), payload);
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
        processingImages,
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
