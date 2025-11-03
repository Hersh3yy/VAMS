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
            uploadItem.error =
                'Upload failed after multiple retries. Please refresh the page and try again.';
            return;
        }

        const { file } = uploadItem;

        // Update status to uploading
        uploadItem.status = 'uploading';
        uploadItem.progress = 0;

        const formData = new FormData();
        const fieldName = config.fieldName || 'images[]';
        formData.append(fieldName, file);

        // Helper function to handle errors properly
        const handleError = (errorMessage: string) => {
            console.error('Upload error:', errorMessage, {
                file_name: file.name,
                retry_count: retryCount,
                timestamp: new Date().toISOString()
            });
            uploadItem.status = 'error';
            uploadItem.error = errorMessage;
            if (config.onError) {
                config.onError(errorMessage);
            }
        };

        // Get CSRF token using the composable
        let token = getCsrfToken();
        if (!token) {
            console.warn('CSRF token not found, attempting to refresh', {
                file_name: file.name,
                retry_count: retryCount,
                timestamp: new Date().toISOString()
            });

            if (retryCount < 2) {
                try {
                    token = await refreshToken();
                    console.log('CSRF token refreshed successfully', {
                        file_name: file.name,
                        token: token.substring(0, 8) + '...',
                        timestamp: new Date().toISOString()
                    });
                } catch (refreshError) {
                    console.error('Failed to refresh CSRF token', {
                        file_name: file.name,
                        error: refreshError,
                        timestamp: new Date().toISOString()
                    });
                    handleError('Security token not found. Please refresh the page and try again.');
                    return;
                }
            } else {
                console.error('CSRF token not found after retries', {
                    file_name: file.name,
                    retry_count: retryCount,
                    timestamp: new Date().toISOString()
                });
                handleError('Security token not found. Please refresh the page and try again.');
                return;
            }
        }

        console.log('Starting upload with CSRF token', {
            file_name: file.name,
            file_size: file.size,
            token: token.substring(0, 8) + '...',
            endpoint: config.endpoint,
            retry_count: retryCount,
            timestamp: new Date().toISOString()
        });

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
            if (uploadItem.status === 'uploading') {
                uploadItem.status = 'processing';
                uploadItem.progress = 85;
            }
        });

        // Handle response - wrap in Promise to properly handle errors
        const responsePromise = new Promise<void>((resolve, reject) => {
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
                    resolve();
                } else if (xhr.status === 419) {
                    // CSRF token mismatch - try to refresh token and retry
                    console.warn('CSRF token mismatch detected', {
                        file_name: file.name,
                        retry_count: retryCount,
                        status: xhr.status,
                        response: xhr.responseText,
                        timestamp: new Date().toISOString()
                    });

                    if (retryCount < 2) {
                        // Only retry twice
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
                            .catch(refreshError => {
                                console.error('Failed to refresh CSRF token for retry', {
                                    file_name: file.name,
                                    retry_count: retryCount,
                                    error: refreshError,
                                    timestamp: new Date().toISOString()
                                });
                                handleError('Session expired. Please refresh the page and try again.');
                            });
                    } else {
                        handleError('Session expired after multiple retries. Please refresh the page and try again.');
                    }
                    resolve(); // Don't reject, we've handled the error
                } else if (xhr.status === 422) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        // Extract user-friendly error message
                        let errorMessage = 'Upload failed';
                        
                        if (errorData?.message) {
                            errorMessage = errorData.message;
                        } else if (errorData?.errors) {
                            // Laravel validation errors are in errors object
                            const errors = errorData.errors;
                            // Check for specific field errors
                            if (errors['images'] && Array.isArray(errors['images'])) {
                                errorMessage = errors['images'][0];
                            } else if (errors['images.*'] && Array.isArray(errors['images.*'])) {
                                errorMessage = errors['images.*'][0];
                            } else {
                                // Get first error message from any field
                                const firstErrorKey = Object.keys(errors)[0];
                                if (firstErrorKey && Array.isArray(errors[firstErrorKey])) {
                                    errorMessage = errors[firstErrorKey][0];
                                }
                            }
                        }
                        
                        handleError(errorMessage);
                        resolve(); // Don't reject, we've handled the error
                    } catch (parseError) {
                        // If JSON parsing fails, try to extract error from response text
                        const responseText = xhr.responseText;
                        if (responseText.includes('exceed') || responseText.includes('max')) {
                            handleError('File size exceeds the maximum limit of 1.99MB. Please compress or resize your image before uploading.');
                        } else {
                            handleError('Upload failed with validation errors. Please check your file and try again.');
                        }
                        resolve(); // Don't reject, we've handled the error
                    }
                } else if (xhr.status === 413) {
                    // Payload Too Large - server rejected before reaching Laravel (Nginx limit)
                    handleError(
                        `File "${file.name}" exceeds the server's maximum file size limit (1.99MB). Please compress or resize your image before uploading.`
                    );
                    resolve(); // Don't reject, we've handled the error
                } else {
                    // Provide more context for other HTTP errors
                    let errorMessage = `Upload failed (HTTP ${xhr.status})`;
                    if (xhr.status >= 500) {
                        errorMessage = 'Server error occurred. Please try again in a moment.';
                    } else if (xhr.status === 0) {
                        errorMessage = `File "${file.name}" may be too large or network connection was lost. Maximum size is 1.99MB.`;
                    } else {
                        errorMessage = `Upload failed with status ${xhr.status}. Please try again.`;
                    }
                    handleError(errorMessage);
                    resolve(); // Don't reject, we've handled the error
                }
            });

            xhr.addEventListener('error', () => {
                // Network-level error (not HTTP response error)
                // This often happens with file size issues at the server level
                handleError(
                    `Network error uploading "${file.name}". This may be due to file size exceeding 1.99MB, network issues, or server limits. Please check your file size and connection.`
                );
                resolve(); // Don't reject, we've handled the error
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
                            handleError('Upload timeout and failed to refresh token');
                        });
                } else {
                    handleError('Upload timeout. The file may be too large or the connection is too slow.');
                }
                resolve(); // Don't reject, we've handled the error
            });
        });

        // Set up the request
        xhr.open('POST', config.endpoint);
        xhr.setRequestHeader('X-CSRF-TOKEN', token);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        // Send the request
        xhr.send(formData);

        // Wait for response (or error/timeout handlers)
        await responsePromise;
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
