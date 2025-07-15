<template>
    <div>
        <h4 class="mb-4 text-center text-lg font-medium text-gray-900">
            Add Text Content
        </h4>
        <p class="mb-8 text-center text-sm text-gray-500">
            Enter text content for your mosaic item.
        </p>

        <div class="mx-auto max-w-2xl space-y-4">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">
                    Title (optional)
                </label>
                <input
                    v-model="title"
                    type="text"
                    placeholder="Text title or heading"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">
                    Content *
                </label>
                <textarea
                    v-model="content"
                    placeholder="Enter your text content here..."
                    rows="8"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    You can use basic formatting and line breaks.
                </p>
            </div>

            <div class="text-center">
                <button
                    @click="submit"
                    :disabled="!content.trim()"
                    class="inline-flex items-center rounded-md border border-transparent px-6 py-3 text-base font-medium text-white shadow-sm transition-colors"
                    :class="
                        content.trim()
                            ? 'bg-blue-600 hover:bg-blue-700'
                            : 'cursor-not-allowed bg-gray-400'
                    "
                >
                    Add Text
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
                            d="M12 4v16m8-8H4"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{
    (e: 'submit', data: { title?: string; content: string }): void;
}>();

const title = ref('');
const content = ref('');

const submit = () => {
    if (content.value.trim()) {
        emit('submit', {
            title: title.value || undefined,
            content: content.value.trim(),
        });
    }
};
</script>
