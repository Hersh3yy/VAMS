<template>
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4 text-center">
            Add Text Content
        </h4>
        <p class="text-sm text-gray-500 mb-8 text-center">
            Enter text content for your mosaic item.
        </p>
        
        <div class="max-w-2xl mx-auto space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Title (optional)
                </label>
                <input
                    v-model="title"
                    type="text"
                    placeholder="Text title or heading"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Content *
                </label>
                <textarea
                    v-model="content"
                    placeholder="Enter your text content here..."
                    rows="8"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">You can use basic formatting and line breaks.</p>
            </div>

            <div class="text-center">
                <button
                    @click="submit"
                    :disabled="!content.trim()"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white transition-colors"
                    :class="content.trim() ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'"
                >
                    Add Text
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
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
            content: content.value.trim()
        });
    }
};
</script> 