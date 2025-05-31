import { ref, inject } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Album, AlbumImage, AlbumVideo, AlbumUploadProgress } from '@/types/album';

export function useAlbum(albumId: string) {
    const uploading = ref(false);
    const uploadProgress = ref(0);
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

        const formData = new FormData();
        formData.append('album_id', albumId);
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
            
            // Track upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    uploadProgress.value = Math.round((e.loaded / e.total) * 100);
                }
            });

            // Handle response
            xhr.addEventListener('load', () => {
                if (xhr.status === 200 || xhr.status === 201) {
                    uploadProgress.value = 100;
                    // Use Inertia's visit to refresh the page with the new data
                    router.visit(route('albums.show', albumId), {
                        preserveScroll: true,
                        preserveState: false, // Set to false to ensure fresh data
                        only: ['album']
                    });
                } else {
                    const errorData = JSON.parse(xhr.responseText);
                    throw new Error(errorData?.message || 'Upload failed');
                }
            });

            xhr.addEventListener('error', () => {
                throw new Error('Network error during upload');
            });

            // Set up the request
            xhr.open('POST', route('album-images.store'));
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            // Send the request
            xhr.send(formData);
            
        } catch (error) {
            console.error('Upload failed:', error);
            showError('Failed to upload images. Please try again.');
        } finally {
            setTimeout(() => {
                uploading.value = false;
                uploadProgress.value = 0;
                // Clear the file input
                if (input) {
                    input.value = '';
                }
            }, 1000); // Show 100% briefly before hiding
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

    const reorderImages = async (fromId: string, toId: string) => {
        try {
            await router.put(route('albums.images.reorder', albumId), {
                from_id: fromId,
                to_id: toId,
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
            await router.post(route('album-images.store-video', albumId), {
                album_id: albumId,
                url,
                title,
                caption,
            }, {
                preserveScroll: true,
                preserveState: false,
                only: ['album'],
                onSuccess: () => {
                    // Success handled by Inertia redirect
                },
                onError: (errors) => {
                    console.error('Add video errors:', errors);
                    const errorMessage = errors.message || errors.url || 'Error adding video';
                    showError(errorMessage);
                }
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