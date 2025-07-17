import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useCsrfToken } from './useCsrfToken';

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
    const { getCsrfToken, refreshCsrfToken: refreshToken } = useCsrfToken();

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

    const uploadSingleFile = async (
        uploadItem: UploadItem,
        config: UploadConfig,
        retryCount = 0
    ) => {
        // Limit retries to prevent infinite loops
        if (retryCount >= 3) {
            console.error('Max retries reached for CSRF token refresh', {
                file_name: uploadItem.file.name,
                retry_count: retryCount,
                timestamp: new Date().toISOString()
            });
            uploadItem.status = 'error';
            uploadItem.error = 'Upload failed after multiple retries. Please refresh the page and try again.';
            return;
        }

        const { file } = uploadItem;

        // Update status to uploading
        uploadItem.status = 'uploading';
        uploadItem.progress = 0;

        const formData = new FormData();
        const fieldName = config.fieldName || 'images[]';
        formData.append(fieldName, file);

        // Get CSRF token using the composable
        const token = getCsrfToken();
        if (!token) {
            console.error('CSRF token not found for upload', {
                file_name: file.name,
                file_size: file.size,
                timestamp: new Date().toISOString()
            });
            uploadItem.status = 'error';
            uploadItem.error = 'Security token not found. Please refresh the page and try again.';
            return;
        }
        
        console.log('Starting upload with CSRF token', {
            file_name: file.name,
            file_size: file.size,
            token: token.substring(0, 8) + '...',
            endpoint: config.endpoint,
            retry_count: retryCount,
            timestamp: new Date().toISOString()
        });

        try {
            // Create XMLHttpRequest for progress tracking
            const xhr = new XMLHttpRequest();
            
            // Set a timeout for the request (100 seconds for large files)
            xhr.timeout = 100000; // 100 seconds
            
            // Track upload start time for smart retry logic
            const uploadStartTime = Date.now();

            // Track upload progress (limit to 80% to save room for processing stage)
            xhr.upload.addEventListener('progress', e => {
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
                        if (config.refreshRoute && config.refreshParams) {
                            setTimeout(() => {
                                router.visit(route(config.refreshRoute!, config.refreshParams), {
                                    preserveScroll: true,
                                    preserveState: false,
                                    onProgress: () => false // Disable Inertia progress bar
                                });
                            }, 500);
                        }
                    } catch (e) {
                        console.warn('Could not parse response:', e);
                    }
                } else if (xhr.status === 419) {
                    // CSRF token mismatch - try to refresh token and retry
                    console.warn('CSRF token mismatch detected', {
                        file_name: file.name,
                        retry_count: retryCount,
                        status: xhr.status,
                        response: xhr.responseText,
                        timestamp: new Date().toISOString()
                    });
                    
                    if (retryCount < 2) { // Only retry twice
                        refreshToken()
                            .then(() => {
                                console.log('CSRF token refreshed, retrying upload', {
                                    file_name: file.name,
                                    retry_count: retryCount + 1,
                                    timestamp: new Date().toISOString()
                                });
                                // Retry the upload with fresh token
                                setTimeout(() => {
                                    uploadSingleFile(uploadItem, config, retryCount + 1);
                                }, 1000);
                            })
                            .catch((refreshError) => {
                                console.error('Failed to refresh CSRF token for retry', {
                                    file_name: file.name,
                                    retry_count: retryCount,
                                    error: refreshError,
                                    timestamp: new Date().toISOString()
                                });
                                uploadItem.status = 'error';
                                uploadItem.error =
                                    'Session expired. Please refresh the page and try again.';
                                if (config.onError) {
                                    config.onError(
                                        'Session expired. Please refresh the page and try again.'
                                    );
                                }
                            });
                    } else {
                        uploadItem.status = 'error';
                        uploadItem.error = 'Session expired after multiple retries. Please refresh the page and try again.';
                        if (config.onError) {
                            config.onError('Session expired after multiple retries. Please refresh the page and try again.');
                        }
                    }
                } else if (xhr.status === 422) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        const errorMessage = errorData?.message || 'Upload failed';
                        throw new Error(errorMessage);
                    } catch (parseError) {
                        throw new Error('Upload failed with validation errors');
                    }
                } else {
                    throw new Error(`Upload failed with status ${xhr.status}`);
                }
            });

            xhr.addEventListener('error', () => {
                throw new Error('Network error during upload');
            });
            
            // Handle timeout - if it's been more than 40 seconds with no progress, try CSRF refresh
            xhr.addEventListener('timeout', () => {
                const timeElapsed = Date.now() - uploadStartTime;
                console.warn('Upload timeout detected', {
                    file_name: file.name,
                    time_elapsed: timeElapsed,
                    retry_count: retryCount,
                    timestamp: new Date().toISOString()
                });
                
                if (timeElapsed > 40000 && retryCount < 2) {
                    // Likely a stale CSRF token causing the hang
                    refreshToken()
                        .then(() => {
                            console.log('CSRF token refreshed after timeout, retrying upload', {
                                file_name: file.name,
                                retry_count: retryCount + 1,
                                timestamp: new Date().toISOString()
                            });
                            setTimeout(() => {
                                uploadSingleFile(uploadItem, config, retryCount + 1);
                            }, 1000);
                        })
                        .catch(() => {
                            throw new Error('Upload timeout and failed to refresh token');
                        });
                } else {
                    throw new Error('Upload timeout');
                }
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
    const completedCount = computed(
        () => uploadQueue.value.filter(item => item.status === 'complete').length
    );

    const errorCount = computed(
        () => uploadQueue.value.filter(item => item.status === 'error').length
    );

    const pendingCount = computed(
        () =>
            uploadQueue.value.filter(
                item =>
                    item.status === 'pending' ||
                    item.status === 'uploading' ||
                    item.status === 'processing'
            ).length
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
        clearCompletedUploads
    };
}
