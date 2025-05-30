import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Album, AlbumImage, AlbumVideo, AlbumUploadProgress } from '@/types/album';

export function useAlbum(albumId: number) {
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
            formData.append('images[]', file);
        });

        try {
            await router.post(route('albums.images.store', albumId), formData, {
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

    const deleteAlbum = async () => {
        try {
            await router.delete(route('albums.destroy', albumId));
        } catch (error) {
            console.error('Delete failed:', error);
        }
    };

    const deleteImage = async (imageId: number) => {
        try {
            await router.delete(route('albums.images.destroy', [albumId, imageId]));
        } catch (error) {
            console.error('Delete failed:', error);
        }
    };

    const reorderImages = async (fromId: number, toId: number) => {
        try {
            await router.put(route('albums.images.reorder', albumId), {
                from_id: fromId,
                to_id: toId,
            });
        } catch (error) {
            console.error('Reorder failed:', error);
        }
    };

    const addVideo = async (url: string, title?: string, caption?: string) => {
        try {
            await router.post(route('albums.videos.store', albumId), {
                url,
                title,
                caption,
            });
        } catch (error) {
            console.error('Add video failed:', error);
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