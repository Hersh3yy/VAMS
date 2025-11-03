import { useUpload, type UploadConfig, type UploadItem } from '@/composables/shared/useUpload';
import { processImagesForUpload } from '@/utils/imageConverter';
import { router } from '@inertiajs/vue3';
import { inject, ref } from 'vue';

// Re-export types for components that need them
export type { UploadConfig, UploadItem };

export function useAlbum(albumId: string) {
    const showConfirmation = ref(false);
    const confirmationTitle = ref('');
    const confirmationMessage = ref('');
    const confirmationAction = ref<(() => void) | null>(null);
    const showError = inject('showError', (message: string) => console.error(message));
    const processingImages = ref(false);

    // Use the generic upload system
    const {
        uploading,
        uploadQueue,
        completedCount,
        errorCount,
        pendingCount,
        overallProgress,
        uploadFiles,
        retryUpload: retryUploadCore,
        removeFromQueue,
        clearCompletedUploads
    } = useUpload();

    const handleFileUpload = async (event: Event) => {
        const input = event.target as HTMLInputElement;
        if (!input.files?.length) return;

        const files = Array.from(input.files);
        const MAX_FILE_SIZE = 1.99 * 1024 * 1024; // 1.99MB in bytes

        // Show processing state
        processingImages.value = true;

        try {
            // Process images: convert to WebP and compress to under 2MB
            const processedFiles = await processImagesForUpload(
                files,
                MAX_FILE_SIZE,
                (processed, total) => {
                    console.log(`Processing images: ${processed}/${total}`);
                }
            );

            if (processedFiles.length === 0) {
                showError('No images could be processed. Please check your files and try again.');
                processingImages.value = false;
                return;
            }

            // Log conversion stats
            const originalTotal = files.reduce((sum, f) => sum + f.size, 0);
            const processedTotal = processedFiles.reduce((sum, f) => sum + f.size, 0);
            const savings = ((1 - processedTotal / originalTotal) * 100).toFixed(1);
            console.log(
                `Image processing complete: ${files.length} files, ${(originalTotal / 1024 / 1024).toFixed(2)}MB → ${(processedTotal / 1024 / 1024).toFixed(2)}MB (${savings}% reduction)`
            );

            // Use the generic upload system with album-specific configuration
            await uploadFiles(processedFiles, {
                endpoint: route('albums.images.store', albumId),
                fieldName: 'images[]',
                entityId: albumId,
                refreshRoute: 'albums.show',
                refreshParams: { album: albumId },
                onError: error => {
                    showError(error);
                }
            });
        } catch (error) {
            console.error('Error processing images:', error);
            showError(
                error instanceof Error
                    ? error.message
                    : 'Failed to process images. Please try again.'
            );
        } finally {
            processingImages.value = false;

            // Clear the file input
            if (input) {
                input.value = '';
            }
        }
    };

    const deleteAlbum = async () => {
        try {
            await router.delete(route('albums.destroy', albumId));
        } catch (error) {
            console.error('Delete failed:', error);
            showError('Failed to delete album. Please try again.');
        }
    };

    const deleteImage = async (imageId: string) => {
        try {
            await router.delete(route('albums.images.destroy', [albumId, imageId]), {
                preserveScroll: true,
                preserveState: false,
                only: ['Album']
            });
        } catch (error) {
            console.error('Delete failed:', error);
            showError('Failed to delete item. Please try again.');
        }
    };

    const reorderImages = async (fromIndex: number, toIndex: number) => {
        try {
            await router.patch(
                route('albums.images.reorder', albumId),
                {
                    from_index: fromIndex,
                    to_index: toIndex
                },
                {
                    preserveScroll: true,
                    preserveState: false,
                    only: ['Album']
                }
            );
        } catch (error) {
            console.error('Reorder failed:', error);
            showError('Failed to reorder images. Please try again.');
        }
    };

    const addVideo = async (url: string, title: string, caption: string) => {
        try {
            await router.post(
                route('albums.images.store-video', albumId),
                {
                    url,
                    title,
                    caption
                },
                {
                    preserveScroll: true,
                    preserveState: false,
                    only: ['Album']
                }
            );
        } catch (error) {
            console.error('Video upload failed:', error);
            showError('Failed to add video. Please try again.');
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

    // Wrapper function for retryUpload to provide config
    const retryUpload = (uploadItem: UploadItem) => {
        const config: UploadConfig = {
            endpoint: route('albums.images.store', albumId),
            fieldName: 'images[]',
            entityId: albumId,
            refreshRoute: 'albums.show',
            refreshParams: { album: albumId },
            onError: error => {
                showError(error);
            }
        };
        return retryUploadCore(uploadItem, config);
    };

    return {
        uploading,
        uploadQueue,
        completedCount,
        errorCount,
        pendingCount,
        overallProgress,
        processingImages,
        showConfirmation,
        confirmationTitle,
        confirmationMessage,
        handleFileUpload,
        retryUpload,
        removeFromQueue,
        clearCompletedUploads,
        deleteAlbum,
        deleteImage,
        reorderImages,
        addVideo,
        showConfirmationDialog,
        confirmAction,
        cancelConfirmation
    };
}
