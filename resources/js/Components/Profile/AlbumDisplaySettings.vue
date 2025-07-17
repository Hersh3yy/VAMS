<script setup lang="ts">
const props = defineProps<{
    modelValue: {
        caption: boolean;
        altText: boolean;
        dateCreated: boolean;
        location: boolean;
        tags: boolean;
        title: boolean;
        author: boolean;
    };
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
}>();

const handleChange = (key: string, event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target) {
        emit('update:modelValue', {
            ...props.modelValue,
            [key]: target.checked
        });
    }
};
</script>

<template>
    <div class="mt-6">
        <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">
            Album Image Display Settings
        </h3>
        <div class="space-y-4">
            <div class="flex items-center">
                <input
                    id="title"
                    type="checkbox"
                    :checked="props.modelValue.title"
                    @change="handleChange('title', $event)"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <label for="title" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    Show Title
                </label>
            </div>
            <div class="flex items-center">
                <input
                    id="author"
                    type="checkbox"
                    :checked="props.modelValue.author"
                    @change="handleChange('author', $event)"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <label for="author" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    Show Author
                </label>
            </div>
            <div class="flex items-center">
                <input
                    id="caption"
                    type="checkbox"
                    :checked="props.modelValue.caption"
                    @change="handleChange('caption', $event)"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <label for="caption" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    Show Caption
                </label>
            </div>
            <div class="flex items-center">
                <input
                    id="altText"
                    type="checkbox"
                    :checked="props.modelValue.altText"
                    @change="handleChange('altText', $event)"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <label for="altText" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    Show Alt Text
                </label>
            </div>
            <div class="flex items-center">
                <input
                    id="dateCreated"
                    type="checkbox"
                    :checked="props.modelValue.dateCreated"
                    @change="handleChange('dateCreated', $event)"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <label for="dateCreated" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    Show Date Created
                </label>
            </div>
            <div class="flex items-center">
                <input
                    id="location"
                    type="checkbox"
                    :checked="props.modelValue.location"
                    @change="handleChange('location', $event)"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <label for="location" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    Show Location
                </label>
            </div>
            <div class="flex items-center">
                <input
                    id="tags"
                    type="checkbox"
                    :checked="props.modelValue.tags"
                    @change="handleChange('tags', $event)"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
                <label for="tags" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    Show Tags
                </label>
            </div>
        </div>
    </div>
</template>
