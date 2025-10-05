<template>
    <div>
        <Head :title="entry.title" />
        
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h1 class="text-3xl font-bold mb-2">{{ entry.title }}</h1>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <span class="inline-block bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs mr-2">
                                        {{ entry.status }}
                                    </span>
                                    Created {{ new Date(entry.created_at).toLocaleDateString() }}
                                    <span v-if="entry.updated_at !== entry.created_at">
                                        • Updated {{ new Date(entry.updated_at).toLocaleDateString() }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <Link :href="route('entries.edit', entry.id)" 
                                      class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    Edit
                                </Link>
                                <Link :href="route('entries.index')" 
                                      class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    ← Back to I AMS
                                </Link>
                            </div>
                        </div>

                        <div class="prose dark:prose-invert max-w-none">
                            <div class="text-lg leading-relaxed whitespace-pre-wrap">{{ getEntryContent }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    entry: {
        type: Object,
        required: true
    }
})

const getEntryContent = computed(() => {
    if (!props.entry.content) return ''
    
    // Handle JSON content
    if (typeof props.entry.content === 'object') {
        return props.entry.content.statement || props.entry.content.content || ''
    }
    
    return props.entry.content
})
</script>
