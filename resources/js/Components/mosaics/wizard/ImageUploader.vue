<template>
    <div>
        <h4 class="mb-4 text-center text-lg font-medium text-gray-900">Upload Images</h4>
        <p class="mb-8 text-center text-sm text-gray-500">Upload images for your mosaic.</p>

        <div class="mx-auto max-w-md">
            <!-- File Drop Zone -->
            <div
                @dragover.prevent
                @dragenter.prevent="dragActive = true"
                @dragleave.prevent="dragActive = false"
                @drop.prevent="handleDrop"
                class="rounded-lg border-2 border-dashed p-8 text-center transition-colors"
                :class="
                    dragActive
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-300 hover:border-gray-400'
                "
            >
                <div class="space-y-4">
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        />
                    </svg>
                    <div>
                        <p class="text-lg font-medium text-gray-900">Drop images here</p>
                        <p class="text-sm text-gray-500">or click to browse</p>
                    </div>
                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        accept="image/*"
                        class="hidden"
                        @change="handleFileSelect"
                    />
                    <button
                        type="button"
                        @click="fileInput?.click()"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Browse Files
                    </button>
                </div>
            </div>

            <!-- Selected Files Preview -->
            <div v-if="selectedFiles.length > 0" class="mt-6">
                <h5 class="mb-3 text-sm font-medium text-gray-900">Selected Files:</h5>
                <div class="grid grid-cols-2 gap-3">
                    <div v-for="(file, index) in selectedFiles" :key="index" class="group relative">
                        <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                            <img
                                :src="getFilePreview(file)"
                                :alt="file.name"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <button
                            @click="removeFile(index)"
                            class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white opacity-0 transition-opacity group-hover:opacity-100"
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
                        <p class="mt-1 truncate text-xs text-gray-500">
                            {{ file.name }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Upload Progress -->
            <div v-if="uploading" class="mt-6">
                <div class="mb-2 flex justify-between text-sm text-gray-600">
                    <span>Uploading...</span>
                    <span>{{ uploadProgress }}%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200">
                    <div
                        class="h-2 rounded-full bg-blue-600 transition-all duration-300"
                        :style="{ width: `${uploadProgress}%` }"
                    />
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 text-center">
                <p class="mb-4 text-sm text-gray-500">
                    Selected: {{ selectedFiles.length }} image{{
                        selectedFiles.length !== 1 ? 's' : ''
                    }}
                </p>
                <button
                    v-if="selectedFiles.length > 0 && !uploading"
                    @click="uploadFiles"
                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Upload {{ selectedFiles.length }} image{{ selectedFiles.length > 1 ? 's' : '' }}
                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { inject, ref } from 'vue';

const props = defineProps<{}>();

const emit = defineEmits<{
    (e: 'upload', images: any[]): void;
}>();

const showError = inject('showError', (message: string) => console.error(message));

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFiles = ref<File[]>([]);
const dragActive = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);

const handleDrop = (event: DragEvent) => {
    dragActive.value = false;
    const files = Array.from(event.dataTransfer?.files || []);
    addFiles(files);
};

const handleFileSelect = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files || []);
    addFiles(files);
};

const addFiles = (files: File[]) => {
    const imageFiles = files.filter(file => {
        // Check if file is an image and under 30MB
        if (!file.type.startsWith('image/')) {
            showError('Only image files are allowed.');
            return false;
        }
        if (file.size > 30 * 1024 * 1024) {
            // 30MB in bytes
            showError('File size must be less than 30MB.');
            return false;
        }
        return true;
    });

    selectedFiles.value.push(...imageFiles);
};

const removeFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
};

const getFilePreview = (file: File): string => {
    return URL.createObjectURL(file);
};

const uploadFiles = async () => {
    if (selectedFiles.value.length === 0) return;

    uploading.value = true;
    uploadProgress.value = 0;

    try {
        // Simulate upload progress
        const interval = setInterval(() => {
            uploadProgress.value = Math.min(uploadProgress.value + 10, 90);
        }, 200);

        // Here you would normally upload to your server
        // For now, we'll simulate it
        await new Promise(resolve => setTimeout(resolve, 2000));

        clearInterval(interval);
        uploadProgress.value = 100;

        // Convert files to a format the parent expects
        const uploadedImages = selectedFiles.value.map((file, index) => ({
            id: `temp_${Date.now()}_${index}`,
            path: getFilePreview(file),
            title: file.name,
            file
        }));

        emit('upload', uploadedImages);
    } catch (error) {
        showError('Failed to upload images. Please try again.');
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
    }
};
</script>
