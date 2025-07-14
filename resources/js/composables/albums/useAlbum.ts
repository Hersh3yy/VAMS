import { ref, inject, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Album, AlbumImage, AlbumVideo, AlbumUploadProgress } from '@/types/album';

export interface UploadItem {
    id: string;
    file: File;
    status: 'pending' | 'uploading' | 'processing' | 'complete' | 'error';
    progress: number;
    error?: string;
    result?: AlbumImage;
}

export function useAlbum(albumId: string) {
    const uploading = ref(false);
    const uploadQueue = ref<UploadItem[]>([]);
    const showConfirmation = ref(false);
    const confirmationTitle = ref('');
    const confirmationMessage = ref('');
    const confirmationAction = ref<(() => void) | null>(null);
    const showError = inject('showError', (message: string) => console.error(message));

    const handleFileUpload = async (event: Event) => {
        const input = event.target as HTMLInputElement;
        if (!input.files?.length) return;

        // Create upload queue for each file
        const files = Array.from(input.files);
        const newUploads: UploadItem[] = files.map(file => ({
            id: `upload_${Date.now()}_${Math.random()}`,
            file,
            status: 'pending',
            progress: 0
        }));

        uploadQueue.value.push(...newUploads);
        uploading.value = true;

        // Process each file individually
        for (const uploadItem of newUploads) {
            await uploadSingleFile(uploadItem);
        }

        // Clear the file input
        if (input) {
            input.value = '';
        }
    };

    const uploadSingleFile = async (uploadItem: UploadItem) => {
        const { file } = uploadItem;
        
        // Update status to uploading
        uploadItem.status = 'uploading';
        uploadItem.progress = 0;

        const formData = new FormData();
        formData.append('images[]', file);

        // Get CSRF token from meta tag
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            uploadItem.status = 'error';
            uploadItem.error = 'Security token not found. Please refresh the page and try again.';
            return;
        }

        try {
            // Create XMLHttpRequest for progress tracking
            const xhr = new XMLHttpRequest();
            
            // Track upload progress (limit to 80% to save room for processing stage)
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && uploadItem.status === 'uploading') {
                    uploadItem.progress = Math.round((e.loaded / e.total) * 80);
                }
            });

            // When upload completes, switch to processing stage
            xhr.upload.addEventListener('load', () => {
                uploadItem.status = 'processing';
                uploadItem.progress = 85;
            });

            // Handle response
            xhr.addEventListener('load', () => {
                if (xhr.status === 200 || xhr.status === 201) {
                    uploadItem.status = 'complete';
                    uploadItem.progress = 100;
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.images && response.images.length > 0) {
                            uploadItem.result = response.images[0];
                        }
                    } catch (e) {
                        console.warn('Could not parse response:', e);
                    }

                    // Refresh the page to show the new image
                    setTimeout(() => {
                        router.visit(route('albums.show', albumId), {
                            preserveScroll: true,
                            preserveState: false,
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
            uploadItem.status = 'error';
            uploadItem.error = error instanceof Error ? error.message : 'Upload failed';
        }
    };

    const retryUpload = async (uploadItem: UploadItem) => {
        uploadItem.error = undefined;
        await uploadSingleFile(uploadItem);
    };

    const removeFromQueue = (uploadItem: UploadItem) => {
        const index = uploadQueue.value.findIndex(item => item.id === uploadItem.id);
        if (index !== -1) {
            uploadQueue.value.splice(index, 1);
        }
        
        // If no more uploads, hide the upload section
        if (uploadQueue.value.length === 0) {
            uploading.value = false;
        }
    };

    const clearCompletedUploads = () => {
        uploadQueue.value = uploadQueue.value.filter(item => item.status !== 'complete');
        if (uploadQueue.value.length === 0) {
            uploading.value = false;
        }
    };

    // Computed properties for better UX
    const completedCount = computed(() => 
        uploadQueue.value.filter(item => item.status === 'complete').length
    );

    const errorCount = computed(() => 
        uploadQueue.value.filter(item => item.status === 'error').length
    );

    const pendingCount = computed(() => 
        uploadQueue.value.filter(item => item.status === 'pending' || item.status === 'uploading' || item.status === 'processing').length
    );

    const overallProgress = computed(() => {
        if (uploadQueue.value.length === 0) return 0;
        const totalProgress = uploadQueue.value.reduce((sum, item) => sum + item.progress, 0);
        return Math.round(totalProgress / uploadQueue.value.length);
    });

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
                to_index: toIndex
            }, {
                preserveScroll: true,
                preserveState: false,
                only: ['album']
            });
        } catch (error) {
            console.error('Reorder failed:', error);
            showError('Failed to reorder images. Please try again.');
        }
    };

    const addVideo = async (url: string, title: string, caption: string) => {
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
        cancelConfirmation,
    };
} 