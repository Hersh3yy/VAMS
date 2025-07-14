import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export interface UploadItem {
    id: string;
    file: File;
    status: 'pending' | 'uploading' | 'processing' | 'complete' | 'error';
    progress: number;
    error?: string;
    result?: any;
}

export interface UploadConfig {
    endpoint: string;
    fieldName?: string;
    entityId?: string;
    onSuccess?: (result: any) => void;
    onError?: (error: string) => void;
    refreshRoute?: string;
    refreshParams?: Record<string, any>;
}

export function useUpload() {
    const uploading = ref(false);
    const uploadQueue = ref<UploadItem[]>([]);

    const uploadFiles = async (files: File[], config: UploadConfig) => {
        // Create upload queue for each file
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
            await uploadSingleFile(uploadItem, config);
        }
    };

    const uploadSingleFile = async (uploadItem: UploadItem, config: UploadConfig, retryCount = 0) => {
        const { file } = uploadItem;
        
        // Update status to uploading
        uploadItem.status = 'uploading';
        uploadItem.progress = 0;

        const formData = new FormData();
        const fieldName = config.fieldName || 'images[]';
        formData.append(fieldName, file);

        // Get CSRF token from meta tag
        let token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
                        uploadItem.result = response;
                        
                        // Call success callback if provided
                        if (config.onSuccess) {
                            config.onSuccess(response);
                        }
                        
                        // Refresh the page if route is provided
                        if (config.refreshRoute) {
                            setTimeout(() => {
                                router.visit(config.refreshRoute!, {
                                    preserveScroll: true,
                                    preserveState: false,
                                    onProgress: () => false // Disable Inertia progress bar
                                });
                            }, 500);
                        }
                    } catch (e) {
                        console.warn('Could not parse response:', e);
                    }
                } else if (xhr.status === 419 && retryCount < 2) {
                    // CSRF token mismatch - try to refresh token and retry
                    console.log('CSRF token mismatch, attempting to refresh token...');
                    refreshCsrfToken().then(() => {
                        // Retry the upload with fresh token
                        setTimeout(() => {
                            uploadSingleFile(uploadItem, config, retryCount + 1);
                        }, 1000);
                    }).catch(() => {
                        uploadItem.status = 'error';
                        uploadItem.error = 'Session expired. Please refresh the page and try again.';
                        if (config.onError) {
                            config.onError('Session expired. Please refresh the page and try again.');
                        }
                    });
                } else {
                    const errorData = JSON.parse(xhr.responseText);
                    const errorMessage = errorData?.message || 'Upload failed';
                    throw new Error(errorMessage);
                }
            });

            xhr.addEventListener('error', () => {
                throw new Error('Network error during upload');
            });

            // Set up the request
            xhr.open('POST', config.endpoint);
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            // Send the request
            xhr.send(formData);
            
        } catch (error) {
            console.error('Upload failed:', error);
            uploadItem.status = 'error';
            const errorMessage = error instanceof Error ? error.message : 'Upload failed';
            uploadItem.error = errorMessage;
            if (config.onError) {
                config.onError(errorMessage);
            }
        }
    };

    const refreshCsrfToken = async (): Promise<void> => {
        try {
            // Make a request to get a fresh CSRF token
            const response = await fetch('/sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error('Failed to refresh CSRF token');
            }
            
            // Update the meta tag with the new token
            const newToken = document.querySelector('meta[name="csrf-token"]');
            if (newToken) {
                // The token should be automatically updated by Laravel
                console.log('CSRF token refreshed successfully');
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
            throw error;
        }
    };

    const retryUpload = async (uploadItem: UploadItem, config: UploadConfig) => {
        uploadItem.error = undefined;
        await uploadSingleFile(uploadItem, config);
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

    return {
        uploading,
        uploadQueue,
        completedCount,
        errorCount,
        pendingCount,
        overallProgress,
        uploadFiles,
        retryUpload,
        removeFromQueue,
        clearCompletedUploads,
    };
} 