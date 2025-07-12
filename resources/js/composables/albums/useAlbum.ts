import { ref, inject } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Album, AlbumImage, AlbumVideo, AlbumUploadProgress } from '@/types/album';

export function useAlbum(albumId: string) {
    const uploading = ref(false);
    const uploadProgress = ref(0);
    const uploadStage = ref('uploading'); // 'uploading' | 'processing' | 'complete'
    const showConfirmation = ref(false);
    const confirmationTitle = ref('');
    const confirmationMessage = ref('');
    const confirmationAction = ref<(() => void) | null>(null);
    const showError = inject('showError', (message: string) => console.error(message));

    const handleFileUpload = async (event: Event) => {
        const input = event.target as HTMLInputElement;
        if (!input.files?.length) return;

        uploading.value = true;
        uploadProgress.value = 0;
        uploadStage.value = 'uploading';

        const formData = new FormData();
        Array.from(input.files).forEach(file => {
            formData.append('images[]', file);
        });

        // Get CSRF token from meta tag
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            console.error('CSRF token not found');
            showError('Security token not found. Please refresh the page and try again.');
            uploading.value = false;
            return;
        }

        try {
            // Create XMLHttpRequest for progress tracking
            const xhr = new XMLHttpRequest();
            
            // Track upload progress (limit to 90% to save room for processing stage)
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && uploadStage.value === 'uploading') {
                    // Cap upload progress at 90% to leave room for processing stage
                    uploadProgress.value = Math.round((e.loaded / e.total) * 90);
                }
            });

            // When upload completes, switch to processing stage
            xhr.upload.addEventListener('load', () => {
                uploadStage.value = 'processing';
                uploadProgress.value = 95;
            });

            // Handle response
            xhr.addEventListener('load', () => {
                if (xhr.status === 200 || xhr.status === 201) {
                    uploadStage.value = 'complete';
                    uploadProgress.value = 100;
                    
                    // Brief delay to show completion before refreshing
                    setTimeout(() => {
                        // Use Inertia's visit to refresh the page with the new data
                        router.visit(route('albums.show', albumId), {
                            preserveScroll: true,
                            preserveState: false, // Set to false to ensure fresh data
                            only: ['album']
                        });
                    }, 500);
                } else {
                    const errorData = JSON.parse(xhr.responseText);
                    throw new Error(errorData?.message || 'Upload failed');
                }
            });

            xhr.addEventListener('error', () => {
                throw new Error('Network error during upload');
            });

            // Set up the request
            xhr.open('POST', route('albums.images.store', albumId));
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            // Send the request
            xhr.send(formData);
            
        } catch (error) {
            console.error('Upload failed:', error);
            showError('Failed to upload images. Please try again.');
            // Reset states on error
            uploading.value = false;
            uploadProgress.value = 0;
            uploadStage.value = 'uploading';
            // Clear the file input
            if (input) {
                input.value = '';
            }
        } finally {
            // Only reset if we're at complete stage (successful upload)
            if (uploadStage.value === 'complete') {
                setTimeout(() => {
                    uploading.value = false;
                    uploadProgress.value = 0;
                    uploadStage.value = 'uploading';
                    // Clear the file input
                    if (input) {
                        input.value = '';
                    }
                }, 1500); // Show completion briefly before hiding
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
                only: ['album']
            });
        } catch (error) {
            console.error('Delete failed:', error);
            showError('Failed to delete item. Please try again.');
        }
    };

    const reorderImages = async (fromIndex: number, toIndex: number) => {
        try {
            await router.patch(route('albums.images.reorder', albumId), {
                from_index: fromIndex,
                to_index: toIndex,
            }, {
                preserveScroll: true,
                preserveState: false,
                only: ['album']
            });
        } catch (error) {
            console.error('Reorder failed:', error);
            showError('Failed to reorder items. Please try again.');
        }
    };

    const addVideo = async (url: string, title?: string, caption?: string) => {
        try {
            await router.post(route('albums.images.store-video', albumId), {
                url,
                title,
                caption
            }, {
                preserveScroll: true,
                preserveState: false,
                only: ['album']
            });
        } catch (error) {
            console.error('Add video failed:', error);
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

    return {
        uploading,
        uploadProgress,
        uploadStage,
        showConfirmation,
        confirmationTitle,
        confirmationMessage,
        handleFileUpload,
        deleteAlbum,
        deleteImage,
        reorderImages,
        addVideo,
        showConfirmationDialog,
        confirmAction,
        cancelConfirmation,
    };
} 