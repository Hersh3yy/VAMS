<template>
    <div>
        <Head title="I AMS" />
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-semibold">I AMS</h2>
                            <Link :href="route('entries.create')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create New I AM
                            </Link>
                        </div>

                        <div v-if="entities.length === 0" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No I AMS created yet.</p>
                            <Link :href="route('entries.create')" class="text-blue-500 hover:text-blue-700 mt-2 inline-block">
                                Create your first I AM
                            </Link>
                        </div>

                        <div v-else class="grid gap-4">
                            <div v-for="entry in entities" :key="entry.id" 
                                 class="border dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium mb-2">{{ entry.title }}</h3>
                                        <p class="text-gray-600 dark:text-gray-400 mb-2">{{ entry.content }}</p>
                                        <div class="text-sm text-gray-500 dark:text-gray-500">
                                            <span class="inline-block bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs mr-2">
                                                {{ entry.status }}
                                            </span>
                                            Created {{ new Date(entry.created_at).toLocaleDateString() }}
                                        </div>
                                    </div>
                                    <div class="flex space-x-2 ml-4">
                                        <Link :href="route('entries.show', entry.id)" 
                                              class="text-blue-500 hover:text-blue-700 text-sm">
                                            View
                                        </Link>
                                        <Link :href="route('entries.edit', entry.id)" 
                                              class="text-green-500 hover:text-green-700 text-sm">
                                            Edit
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    entities: {
        type: Array,
        default: () => []
    }
})
</script>
