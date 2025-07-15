<template>
    <div class="text-center">
        <h4 class="mb-4 text-lg font-medium text-gray-900">
            What type of content would you like to add?
        </h4>
        <p class="mb-8 text-sm text-gray-500">
            Choose from images, videos, or text content.
        </p>

        <div class="grid grid-cols-3 gap-6">
            <button
                @click="selectType('image')"
                class="group relative rounded-lg border-2 border-gray-300 bg-white p-6 transition-all duration-200 hover:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary"
                :class="{
                    'border-secondary bg-secondary bg-opacity-20':
                        selectedType === 'image',
                }"
            >
                <div class="flex flex-col items-center">
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100 transition-colors group-hover:bg-secondary group-hover:bg-opacity-20"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-8 w-8 text-gray-600 group-hover:text-secondary"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                    <h5 class="mb-2 text-lg font-medium text-gray-900">
                        Image
                    </h5>
                    <p class="text-sm text-gray-500">Add photos or graphics</p>
                </div>
            </button>

            <button
                @click="selectType('video')"
                class="group relative rounded-lg border-2 border-gray-300 bg-white p-6 transition-all duration-200 hover:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary"
                :class="{
                    'border-secondary bg-secondary bg-opacity-20':
                        selectedType === 'video',
                }"
            >
                <div class="flex flex-col items-center">
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100 transition-colors group-hover:bg-secondary group-hover:bg-opacity-20"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-8 w-8 text-gray-600 group-hover:text-secondary"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                    <h5 class="mb-2 text-lg font-medium text-gray-900">
                        Video
                    </h5>
                    <p class="text-sm text-gray-500">Embed video content</p>
                </div>
            </button>

            <button
                @click="selectType('text')"
                class="group relative rounded-lg border-2 border-gray-300 bg-white p-6 transition-all duration-200 hover:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary"
                :class="{
                    'border-secondary bg-secondary bg-opacity-20':
                        selectedType === 'text',
                }"
            >
                <div class="flex flex-col items-center">
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100 transition-colors group-hover:bg-secondary group-hover:bg-opacity-20"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-8 w-8 text-gray-600 group-hover:text-secondary"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h7"
                            />
                        </svg>
                    </div>
                    <h5 class="mb-2 text-lg font-medium text-gray-900">Text</h5>
                    <p class="text-sm text-gray-500">Add rich text content</p>
                </div>
            </button>
        </div>

        <div class="mt-8">
            <button
                v-if="selectedType"
                @click="confirm"
                class="btn-primary inline-flex items-center"
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

const props = defineProps<{
    itemCount: number | null;
}>();

const emit = defineEmits<{
    (e: 'select', type: string): void;
}>();

const selectedType = ref<string | null>(null);

const selectType = (type: string) => {
    selectedType.value = type;
};

const confirm = () => {
    if (selectedType.value) {
        emit('select', selectedType.value);
    }
};
</script>
