<template>
    <div class="text-center">
        <h4 class="mb-4 text-lg font-medium text-gray-900">
            How would you like to add images?
        </h4>
        <p class="mb-8 text-sm text-gray-500">
            Upload new images or select from existing albums.
        </p>

        <div class="grid grid-cols-2 gap-6">
            <button
                @click="selectSource('upload')"
                class="group relative rounded-lg border-2 border-gray-300 bg-white p-6 transition-all duration-200 hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{
                    'border-blue-500 bg-blue-50': selectedSource === 'upload',
                }"
            >
                <div class="flex flex-col items-center">
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100 transition-colors group-hover:bg-blue-100"
                    >
                        <svg
                            class="h-8 w-8 text-gray-600 group-hover:text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                stroke-width="2"
                            />
                            <polyline
                                points="7,10 12,15 17,10"
                                stroke-width="2"
                            />
                            <line
                                x1="12"
                                y1="15"
                                x2="12"
                                y2="3"
                                stroke-width="2"
                            />
                        </svg>
                    </div>
                    <h5 class="mb-2 text-lg font-medium text-gray-900">
                        Upload New
                    </h5>
                    <p class="text-sm text-gray-500">
                        Upload images from your device
                    </p>
                </div>
            </button>

            <button
                @click="selectSource('album')"
                class="group relative rounded-lg border-2 border-gray-300 bg-white p-6 transition-all duration-200 hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{
                    'border-blue-500 bg-blue-50': selectedSource === 'album',
                }"
            >
                <div class="flex flex-col items-center">
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100 transition-colors group-hover:bg-blue-100"
                    >
                        <svg
                            class="h-8 w-8 text-gray-600 group-hover:text-blue-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <rect
                                x="3"
                                y="3"
                                width="18"
                                height="18"
                                rx="2"
                                ry="2"
                                stroke-width="2"
                            />
                            <rect
                                x="7"
                                y="7"
                                width="3"
                                height="9"
                                stroke-width="2"
                            />
                            <rect
                                x="14"
                                y="7"
                                width="3"
                                height="5"
                                stroke-width="2"
                            />
                        </svg>
                    </div>
                    <h5 class="mb-2 text-lg font-medium text-gray-900">
                        From Album
                    </h5>
                    <p class="text-sm text-gray-500">
                        Choose from existing albums
                    </p>
                </div>
            </button>
        </div>

        <div class="mt-8">
            <button
                v-if="selectedSource"
                @click="confirm"
                class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Continue
                <svg
                    class="ml-2 h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                    ></path>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{
    (e: 'select', source: string): void;
}>();

const selectedSource = ref<string | null>(null);

const selectSource = (source: string) => {
    selectedSource.value = source;
};

const confirm = () => {
    if (selectedSource.value) {
        emit('select', selectedSource.value);
    }
};
</script>
