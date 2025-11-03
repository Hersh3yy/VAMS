<template>
    <div v-if="uploadQueue.length > 0" class="mb-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <UploadProgressHeader
                :completed-count="completedCount"
                :error-count="errorCount"
                :pending-count="pendingCount"
            />

            <UploadProgressBar
                :overall-progress="overallProgress"
                :pending-count="pendingCount"
                :error-count="errorCount"
            />

            <!-- Individual Upload Items -->
            <div class="space-y-3">
                <UploadItem
                    v-for="uploadItem in uploadQueue"
                    :key="uploadItem.id"
                    :file-name="uploadItem.file.name"
                    :file-size="uploadItem.file.size"
                    :status="uploadItem.status"
                    :progress="uploadItem.progress"
                    :error="uploadItem.error"
                    @retry="retryUpload(uploadItem)"
                    @remove="removeFromQueue(uploadItem)"
                />
            </div>

            <!-- Actions -->
            <div
                v-if="completedCount > 0 || errorCount > 0"
                class="mt-4 border-t border-gray-200 pt-4"
            >
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <span v-if="completedCount > 0"
                            >{{ completedCount }} image{{
                                completedCount !== 1 ? 's' : ''
                            }}
                            uploaded successfully</span
                        >
                        <span v-if="errorCount > 0" class="ml-2 text-red-600"
                            >{{ errorCount }} failed</span
                        >
                    </div>
                    <button
                        @click="clearCompletedUploads"
                        class="text-sm font-medium text-gray-600 hover:text-gray-800"
                    >
                        Clear completed
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import UploadItem from '@/Components/molecules/UploadItem.vue';
import UploadProgressBar from '@/Components/molecules/UploadProgressBar.vue';
import UploadProgressHeader from '@/Components/molecules/UploadProgressHeader.vue';
import type { UploadItem as UploadItemType } from '@/composables/albums/useAlbum';

const props = defineProps<{
    uploadQueue: UploadItemType[];
    completedCount: number;
    errorCount: number;
    pendingCount: number;
    overallProgress: number;
}>();

const emit = defineEmits<{
    (e: 'retry', uploadItem: UploadItemType): void;
    (e: 'remove', uploadItem: UploadItemType): void;
    (e: 'clear-completed'): void;
}>();

const retryUpload = (uploadItem: UploadItemType) => {
    emit('retry', uploadItem);
};

const removeFromQueue = (uploadItem: UploadItemType) => {
    emit('remove', uploadItem);
};

const clearCompletedUploads = () => {
    emit('clear-completed');
};
</script>
