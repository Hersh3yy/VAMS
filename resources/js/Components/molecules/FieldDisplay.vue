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

        <!-- Text/Textarea Display (http(s) links become clickable) -->
        <div
            v-else-if="type === 'text' || type === 'textarea'"
            class="text-sm text-gray-900 dark:text-gray-100"
            :class="{ 'whitespace-pre-wrap': type === 'textarea' }"
        >
            <a
                v-if="isHttpUrl(value)"
                :href="formatUrl(value).href"
                target="_blank"
                rel="noopener noreferrer"
                class="break-all text-secondary underline hover:text-indigo-800 dark:hover:text-indigo-300"
                :title="value"
            >
                {{ formatUrl(value, 80).label }}
            </a>
            <template v-else>{{ value || '-' }}</template>
        </div>

        <!-- Empty state (for all remaining types) -->
        <div v-else-if="isEmptyValue(value)" class="text-sm text-gray-500 dark:text-gray-400">
            No data
        </div>

        <!-- Number / Select Display -->
        <div v-else-if="type === 'number'" class="text-sm tabular-nums text-gray-900 dark:text-gray-100">
            {{ formatNumber(value) }}
        </div>

        <div v-else-if="type === 'select'" class="text-sm text-gray-900 dark:text-gray-100">
            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 dark:bg-gray-700">{{ value }}</span>
        </div>

        <!-- Date & time Display (wall-clock time as stored, with its offset) -->
        <div v-else-if="type === 'datetime'" class="text-sm text-gray-900 dark:text-gray-100">
            <time :datetime="String(value)" :title="String(value)">{{ formatDateTime(value) }}</time>
        </div>

        <!-- URL Display -->
        <div v-else-if="type === 'url'" class="text-sm">
            <a
                :href="formatUrl(value).href"
                target="_blank"
                rel="noopener noreferrer"
                class="break-all text-secondary underline hover:text-indigo-800 dark:hover:text-indigo-300"
                :title="String(value)"
            >
                {{ formatUrl(value).label }}
            </a>
        </div>

        <!-- JSON Display: scalar arrays as chips, anything else pretty-printed -->
        <div v-else-if="type === 'json'" class="text-sm">
            <ul v-if="isScalarArray(value)" class="flex flex-wrap gap-2">
                <li
                    v-for="(item, index) in value"
                    :key="index"
                    class="rounded-full bg-gray-100 px-2.5 py-0.5 text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                >
                    {{ item }}
                </li>
            </ul>
            <div v-else>
                <pre
                    class="overflow-x-auto rounded-md bg-gray-50 p-3 font-mono text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-200"
                    :class="{ 'max-h-40 overflow-y-hidden': jsonIsLong && !jsonExpanded }"
                >{{ prettyJson }}</pre>
                <button
                    v-if="jsonIsLong"
                    type="button"
                    class="mt-1 text-xs text-secondary hover:text-indigo-800 dark:hover:text-indigo-300"
                    @click="jsonExpanded = !jsonExpanded"
                >
                    {{ jsonExpanded ? 'Show less' : `Show all ${jsonLineCount} lines` }}
                </button>
            </div>
        </div>

        <!-- Entry Relation Display: related entry titles, linking to each entry -->
        <EntryRelationDisplay v-else-if="type === 'entry_relation'" :value="value" />

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

        <!-- Fallback for unknown types -->
        <div v-else class="text-sm text-gray-900 dark:text-gray-100">
            {{ JSON.stringify(value) }}
        </div>
    </div>
</template>

<script setup lang="ts">
import EntryRelationDisplay from '@/Components/molecules/EntryRelationDisplay.vue'
import {
    formatDateTime,
    formatNumber,
    formatUrl,
    isEmptyValue,
    isHttpUrl,
    isScalarArray,
} from '@/utils/entryFieldFormat'
import { computed, ref } from 'vue'

interface Props {
    label: string
    value: any
    type: string
}

const props = defineProps<Props>()

const JSON_COLLAPSE_LINES = 12

const jsonExpanded = ref(false)

const prettyJson = computed(() =>
    typeof props.value === 'string' ? props.value : JSON.stringify(props.value, null, 2),
)
const jsonLineCount = computed(() => (prettyJson.value ?? '').split('\n').length)
const jsonIsLong = computed(() => jsonLineCount.value > JSON_COLLAPSE_LINES)

const formatFieldName = (name: string): string => {
    return name
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase())
}
</script>
