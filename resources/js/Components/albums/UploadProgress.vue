<template>
    <div v-if="uploadQueue.length > 0" class="mb-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <!-- Header -->
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Uploading Images</h3>
                <div class="flex items-center space-x-4 text-sm text-gray-600">
                    <span>{{ completedCount }} complete</span>
                    <span v-if="errorCount > 0" class="text-red-600">{{ errorCount }} failed</span>
                    <span v-if="pendingCount > 0">{{ pendingCount }} remaining</span>
                </div>
            </div>

            <!-- Overall Progress -->
            <div class="mb-4">
                <div class="mb-2 flex justify-between text-sm text-gray-600">
                    <span>Overall Progress</span>
                    <span>{{ overallProgress }}%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200">
                    <div
                        class="h-2 rounded-full transition-all duration-300"
                        :class="{
                            'bg-blue-600': pendingCount > 0,
                            'bg-green-600': pendingCount === 0 && errorCount === 0,
                            'bg-yellow-500': pendingCount === 0 && errorCount > 0
                        }"
                        :style="{ width: `${overallProgress}%` }"
                    />
                </div>
            </div>

            <!-- Individual Upload Items -->
            <div class="space-y-3">
                <div
                    v-for="uploadItem in uploadQueue"
                    :key="uploadItem.id"
                    class="flex items-center space-x-3 rounded-lg bg-gray-50 p-3"
                >
                    <!-- File Icon -->
                    <div class="flex-shrink-0">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-200"
                        >
                            <svg
                                class="h-6 w-6 text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- File Info and Progress -->
                    <div class="min-w-0 flex-1">
                        <div class="mb-2 flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ uploadItem.file.name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ formatFileSize(uploadItem.file.size) }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <!-- Status Icon -->
                                <div v-if="uploadItem.status === 'pending'" class="h-4 w-4">
                                    <svg
                                        class="h-4 w-4 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <div v-else-if="uploadItem.status === 'uploading'" class="h-4 w-4">
                                    <svg
                                        class="h-4 w-4 animate-spin text-blue-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        />
                                    </svg>
                                </div>
                                <div v-else-if="uploadItem.status === 'processing'" class="h-4 w-4">
                                    <svg
                                        class="h-4 w-4 animate-spin text-yellow-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                </div>
                                <div v-else-if="uploadItem.status === 'complete'" class="h-4 w-4">
                                    <svg
                                        class="h-4 w-4 text-green-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                                <div v-else-if="uploadItem.status === 'error'" class="h-4 w-4">
                                    <svg
                                        class="h-4 w-4 text-red-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </div>

                                <!-- Remove Button -->
                                <button
                                    @click="removeFromQueue(uploadItem)"
                                    class="text-gray-400 transition-colors hover:text-gray-600"
                                    :disabled="
                                        uploadItem.status === 'uploading' ||
                                        uploadItem.status === 'processing'
                                    "
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="h-1.5 w-full rounded-full bg-gray-200">
                            <div
                                class="h-1.5 rounded-full transition-all duration-300"
                                :class="{
                                    'bg-gray-400': uploadItem.status === 'pending',
                                    'bg-blue-600': uploadItem.status === 'uploading',
                                    'bg-yellow-500': uploadItem.status === 'processing',
                                    'bg-green-600': uploadItem.status === 'complete',
                                    'bg-red-600': uploadItem.status === 'error'
                                }"
                                :style="{ width: `${uploadItem.progress}%` }"
                            />
                        </div>

                        <!-- Status Text -->
                        <div class="mt-1 flex items-center justify-between">
                            <span class="text-xs text-gray-500">
                                <template v-if="uploadItem.status === 'pending'">
                                    Waiting to upload...
                                </template>
                                <template v-else-if="uploadItem.status === 'uploading'">
                                    Uploading...
                                </template>
                                <template v-else-if="uploadItem.status === 'processing'">
                                    Processing image...
                                </template>
                                <template v-else-if="uploadItem.status === 'complete'">
                                    Upload complete!
                                </template>
                                <template v-else-if="uploadItem.status === 'error'">
                                    {{ uploadItem.error }}
                                </template>
                            </span>
                            <span class="text-xs text-gray-500">{{ uploadItem.progress }}%</span>
                        </div>

                        <!-- Error Message -->
                        <div v-if="uploadItem.status === 'error'" class="mt-2">
                            <button
                                @click="retryUpload(uploadItem)"
                                class="text-xs font-medium text-blue-600 hover:text-blue-800"
                            >
                                Retry upload
                            </button>
                        </div>
                    </div>
                </div>
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
import type { UploadItem } from '@/composables/albums/useAlbum';

const props = defineProps<{
    uploadQueue: UploadItem[];
    completedCount: number;
    errorCount: number;
    pendingCount: number;
    overallProgress: number;
}>();

const emit = defineEmits<{
    (e: 'retry', uploadItem: UploadItem): void;
    (e: 'remove', uploadItem: UploadItem): void;
    (e: 'clear-completed'): void;
}>();

const retryUpload = (uploadItem: UploadItem) => {
    emit('retry', uploadItem);
};

const removeFromQueue = (uploadItem: UploadItem) => {
    emit('remove', uploadItem);
};

const clearCompletedUploads = () => {
    emit('clear-completed');
};

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>
