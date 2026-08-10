<template>
    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ label }}
        </label>

        <!-- Checkbox Display -->
        <div v-if="type === 'checkbox'" class="flex items-center">
            <div
                class="flex h-5 w-5 items-center justify-center rounded border-2"
                :class="value ? 'border-green-500 bg-green-500' : 'border-gray-300 dark:border-gray-600'"
            >
                <svg
                    v-if="value"
                    class="h-3 w-3 text-white"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                    />
                </svg>
            </div>
            <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                {{ value ? 'Yes' : 'No' }}
            </span>
        </div>

        <!-- Text/Textarea Display -->
        <div 
            v-else-if="type === 'text' || type === 'textarea'"
            class="text-sm text-gray-900 dark:text-gray-100"
            :class="{ 'whitespace-pre-wrap': type === 'textarea' }"
        >
            {{ value || '-' }}
        </div>

        <!-- Repeatable Section Display -->
        <div v-else-if="type === 'repeatable' && Array.isArray(value)" class="space-y-3">
            <div 
                v-for="(item, index) in value" 
                :key="index"
                class="border rounded-lg p-3 bg-gray-50 dark:bg-gray-700"
            >
                <div class="space-y-2">
                    <div v-for="[key, val] in Object.entries(item)" :key="key" class="text-sm">
                        <span class="font-medium text-gray-600 dark:text-gray-400">
                            {{ formatFieldName(key) }}:
                        </span>
                        <span class="ml-2 text-gray-900 dark:text-gray-100">
                            {{ val || '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Collection Display -->
        <div v-else-if="type === 'image_collection' && Array.isArray(value)" class="grid grid-cols-3 gap-4">
            <div 
                v-for="(image, index) in value"
                :key="index"
                class="border rounded-lg overflow-hidden"
            >
                <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                    <img 
                        v-if="image.url || image.path"
                        :src="image.url || image.path || ''"
                        :alt="image.alt || `Image ${index + 1}`"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div v-if="image.alt" class="p-2 bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
                    {{ image.alt }}
                </div>
            </div>
        </div>

        <!-- Object/Group Display -->
        <div 
            v-else-if="type === 'object' && typeof value === 'object' && value !== null"
            class="border rounded-lg p-3 bg-gray-50 dark:bg-gray-700 space-y-2"
        >
            <div v-for="[key, val] in Object.entries(value)" :key="key" class="text-sm">
                <span class="font-medium text-gray-600 dark:text-gray-400">
                    {{ formatFieldName(key) }}:
                </span>
                <span class="ml-2 text-gray-900 dark:text-gray-100">
                    {{ val || '-' }}
                </span>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0)" class="text-sm text-gray-500 dark:text-gray-400">
            No data
        </div>

        <!-- Fallback for unknown types -->
        <div v-else class="text-sm text-gray-900 dark:text-gray-100">
            {{ JSON.stringify(value) }}
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    label: string
    value: any
    type: string
}

defineProps<Props>()

const formatFieldName = (name: string): string => {
    return name
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase())
}
</script>

