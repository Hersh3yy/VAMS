import { useUpload, type UploadConfig, type UploadItem } from '@/composables/shared/useUpload';
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
        const MAX_FILE_SIZE = 20 * 1024 * 1024; // 20MB in bytes

        // Validate file sizes before upload
        const oversizedFiles = files.filter(file => file.size > MAX_FILE_SIZE);
        
        if (oversizedFiles.length > 0) {
            const fileNames = oversizedFiles.map(f => f.name).join(', ');
            const fileSizes = oversizedFiles.map(f => 
                `${f.name} (${(f.size / 1024 / 1024).toFixed(2)}MB)`
            ).join(', ');
            
            showError(
                `The following files exceed the 20MB limit: ${fileSizes}. Please compress or resize these images before uploading.`
            );
            return;
        }

        // Use the generic upload system with album-specific configuration
        await uploadFiles(files, {
            endpoint: route('albums.images.store', albumId),
            fieldName: 'images[]',
            entityId: albumId,
            refreshRoute: 'albums.show',
            refreshParams: { album: albumId },
            onError: error => {
                showError(error);
            }
        });

        // Clear the file input
        if (input) {
            input.value = '';
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
                only: ['album']
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
                    only: ['album']
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
                    only: ['album']
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
