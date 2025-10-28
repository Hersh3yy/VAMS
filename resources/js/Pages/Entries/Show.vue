<template>
    <div>
        <Head :title="entry.title" />
        
        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-lg">{{ entry.entry_type?.name?.charAt(0) || 'E' }}</span>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ entry.title }}</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ entry.entry_type?.name || 'Entry' }}</p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <Link :href="route('entries.edit', entry.id)" 
                                  class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit</span>
                            </Link>
                            <button
                                @click="deleteEntry"
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center space-x-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Status and Meta Information -->
                    <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                              :class="entry.status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            {{ entry.status }}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Created {{ formatDate(entry.created_at) }}
                        </span>
                        <span v-if="entry.updated_at !== entry.created_at" class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Updated {{ formatDate(entry.updated_at) }}
                        </span>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-8">
                        <!-- Entry Type Header -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                {{ entry.entry_type?.name || 'Entry' }}
                            </h2>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                {{ entry.entry_type?.description || 'Personal entry' }}
                            </p>
                        </div>

                        <!-- Content -->
                        <div class="prose prose-lg dark:prose-invert max-w-none">
                            <div class="text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap bg-gray-50 dark:bg-gray-700 p-6 rounded-xl border-l-4 border-blue-500">
                                {{ getEntryContent }}
                            </div>
                        </div>

                        <!-- Images Section (if any) -->
                        <div v-if="entry.images && entry.images.length > 0" class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Images</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="image in entry.images" :key="image.id" class="relative group">
                                    <img :src="image.url" :alt="image.alt_text || 'Entry image'" 
                                         class="w-full h-48 object-cover rounded-lg shadow-md group-hover:shadow-lg transition-shadow duration-200">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="mt-8 text-center">
                    <Link :href="route('entries.index', { type: entry.entry_type?.slug })" 
                          class="inline-flex items-center px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to {{ entry.entry_type?.name || 'Entries' }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
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

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const deleteEntry = () => {
    const entryName = props.entry.entry_type?.name || 'entry'
    if (confirm(`Are you sure you want to delete this ${entryName}? This action cannot be undone.`)) {
        router.delete(route('entries.destroy', props.entry.id), {
            onSuccess: () => {
                router.visit(route('entries.index', { type: props.entry.entry_type?.slug }))
            }
        })
    }
}
</script>
