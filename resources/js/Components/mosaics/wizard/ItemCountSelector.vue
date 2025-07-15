<template>
    <div class="text-center">
        <h4 class="mb-4 text-lg font-medium text-gray-900">
            How many items would you like to add?
        </h4>
        <p class="mb-8 text-sm text-gray-500">
            Choose whether to add a single item or create a double-width item.
        </p>

        <div class="grid grid-cols-2 gap-6">
            <button
                @click="selectCount(1)"
                class="group relative rounded-lg border-2 border-gray-300 bg-white p-6 transition-all duration-200 hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{ 'border-blue-500 bg-blue-50': selectedCount === 1 }"
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
                                y="4"
                                width="18"
                                height="18"
                                rx="2"
                                ry="2"
                                stroke-width="2"
                            />
                            <line
                                x1="16"
                                y1="2"
                                x2="16"
                                y2="6"
                                stroke-width="2"
                            />
                            <line
                                x1="8"
                                y1="2"
                                x2="8"
                                y2="6"
                                stroke-width="2"
                            />
                            <line
                                x1="3"
                                y1="10"
                                x2="21"
                                y2="10"
                                stroke-width="2"
                            />
                        </svg>
                    </div>
                    <h5 class="mb-2 text-lg font-medium text-gray-900">
                        Single Item
                    </h5>
                    <p class="text-sm text-gray-500">
                        Add one item to the column
                    </p>
                </div>
            </button>

            <button
                @click="selectCount(2)"
                class="group relative rounded-lg border-2 border-gray-300 bg-white p-6 transition-all duration-200 hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{ 'border-blue-500 bg-blue-50': selectedCount === 2 }"
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
                                y="4"
                                width="7"
                                height="18"
                                rx="2"
                                ry="2"
                                stroke-width="2"
                            />
                            <rect
                                x="14"
                                y="4"
                                width="7"
                                height="18"
                                rx="2"
                                ry="2"
                                stroke-width="2"
                            />
                        </svg>
                    </div>
                    <h5 class="mb-2 text-lg font-medium text-gray-900">
                        Double Item
                    </h5>
                    <p class="text-sm text-gray-500">
                        Add two items side by side
                    </p>
                </div>
            </button>
        </div>

        <div class="mt-8">
            <button
                v-if="selectedCount"
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
    (e: 'select', count: number): void;
}>();

const selectedCount = ref<number | null>(null);

const selectCount = (count: number) => {
    selectedCount.value = count;
};

const confirm = () => {
    if (selectedCount.value) {
        emit('select', selectedCount.value);
    }
};
</script>
