import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useMedia() {
    const uploading = ref(false);
    const uploadProgress = ref(0);
    const showConfirmation = ref(false);
    const confirmationTitle = ref('');
    const confirmationMessage = ref('');
    const confirmationAction = ref<(() => void) | null>(null);

    const handleFileUpload = async (event: Event, endpoint: string) => {
        const input = event.target as HTMLInputElement;
        if (!input.files?.length) return;

        uploading.value = true;
        uploadProgress.value = 0;

        const formData = new FormData();
        Array.from(input.files).forEach(file => {
            formData.append('media', file);
        });

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                throw new Error('Upload failed');
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Upload failed:', error);
            throw error;
        } finally {
            uploading.value = false;
            uploadProgress.value = 0;
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
        showConfirmationDialog,
        confirmAction,
        cancelConfirmation,
    };
} 