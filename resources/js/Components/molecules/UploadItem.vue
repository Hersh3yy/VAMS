<template>
    <div
        class="flex items-center space-x-3 rounded-lg p-3"
        :class="{
            'bg-gray-50': status !== 'error',
            'bg-red-50 border-2 border-red-200': status === 'error'
        }"
    >
        <!-- File Icon -->
        <div class="flex-shrink-0">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-200">
                <svg class="h-6 w-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <p class="truncate text-sm font-medium text-gray-900">{{ fileName }}</p>
                    <p class="text-xs text-gray-500">{{ formattedSize }}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Status Icon -->
                    <UploadItemStatusIcon :status="status" />

                    <!-- Remove Button -->
                    <button
                        @click="$emit('remove')"
                        class="text-gray-400 transition-colors hover:text-gray-600"
                        :disabled="status === 'uploading' || status === 'processing'"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        'bg-gray-400': status === 'pending',
                        'bg-blue-600': status === 'uploading',
                        'bg-yellow-500': status === 'processing',
                        'bg-green-600': status === 'complete',
                        'bg-red-600': status === 'error'
                    }"
                    :style="{ width: `${progress}%` }"
                />
            </div>

            <!-- Status Text -->
            <div class="mt-1 flex items-center justify-between">
                <span
                    class="text-xs"
                    :class="{
                        'text-gray-500': status !== 'error',
                        'font-semibold text-red-700': status === 'error'
                    }"
                >
                    {{ statusText }}
                </span>
                <span
                    class="text-xs"
                    :class="{
                        'text-gray-500': status !== 'error',
                        'font-semibold text-red-700': status === 'error'
                    }"
                >
                    {{ progress }}%
                </span>
            </div>

            <!-- Error Message Box -->
            <UploadItemErrorBox
                v-if="status === 'error'"
                :error="error"
                @retry="$emit('retry')"
                @remove="$emit('remove')"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import UploadItemErrorBox from '@/Components/molecules/UploadItemErrorBox.vue';
import UploadItemStatusIcon from '@/Components/atoms/UploadItemStatusIcon.vue';

type UploadStatus = 'pending' | 'uploading' | 'processing' | 'complete' | 'error';

const props = defineProps<{
    fileName: string;
    fileSize: number;
    status: UploadStatus;
    progress: number;
    error?: string;
}>();

defineEmits<{
    (e: 'retry'): void;
    (e: 'remove'): void;
}>();

const formattedSize = computed(() => {
    if (props.fileSize === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(props.fileSize) / Math.log(k));
    return parseFloat((props.fileSize / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
});

const statusText = computed(() => {
    switch (props.status) {
        case 'pending':
            return 'Waiting to upload...';
        case 'uploading':
            return 'Uploading...';
        case 'processing':
            return 'Processing image...';
        case 'complete':
            return 'Upload complete!';
        case 'error':
            return '❌ Upload Failed';
        default:
            return '';
    }
});
</script>

