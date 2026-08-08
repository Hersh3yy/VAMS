<template>
    <div>
        <Head :title="`Edit ${entry.title}`" />
        
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-semibold">Edit {{ entry.entry_type?.name || 'Entry' }}</h1>
                            <Link :href="route('entries.show', entry.id)" class="text-gray-500 hover:text-gray-700">
                                ← Back to {{ entry.entry_type?.name || 'Entry' }}
                            </Link>
                        </div>

                        <!-- Debug info -->
                        <div v-if="!isComplexEntryType" class="mb-4 p-3 bg-yellow-100 dark:bg-yellow-900 rounded">
                            <p class="text-sm">Using simple form. Entry type: {{ entry.entry_type?.name || 'Unknown' }}</p>
                            <p class="text-xs">Has field_config: {{ !!entry.entry_type?.field_config }}</p>
                            <p class="text-xs">Field config length: {{ entry.entry_type?.field_config?.length || 0 }}</p>
                        </div>

                        <!-- Use Dynamic Form for complex entry types, fallback to simple form for I AM -->
                        <DynamicEntryForm
                            v-if="isComplexEntryType && entry.entry_type"
                            :entryType="entry.entry_type"
                            :entry="entry"
                            submitText="Save Changes"
                            :showStatus="true"
                            :showDelete="true"
                            :deleteText="`Delete ${entry.entry_type?.name || 'Entry'}`"
                            @cancel="router.visit(route('entries.show', entry.id))"
                            @submit="handleDynamicSubmit"
                            @delete="deleteEntry"
                        />
                        
                        <!-- Simple form for I AM entries (backward compatibility) -->
                        <form v-else @submit.prevent="form.patch(route('entries.update', entry.id))" class="space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Title
                                </label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    :placeholder="`Give your ${entry.entry_type?.name || 'entry'} a title...`"
                                    required
                                />
                                <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Content
                                </label>
                                <textarea
                                    id="content"
                                    v-model="form.content"
                                    rows="6"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    :placeholder="`Enter ${entry.entry_type?.name?.toLowerCase() || 'entry'} content...`"
                                    required
                                ></textarea>
                                <div v-if="form.errors.content" class="text-red-600 text-sm mt-1">
                                    {{ form.errors.content }}
                                </div>
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Status
                                </label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                                <div v-if="form.errors.status" class="text-red-600 text-sm mt-1">
                                    {{ form.errors.status }}
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <Link :href="route('entries.show', entry.id)" 
                                      class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Saving...</span>
                                    <span v-else>Save Changes</span>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Delete button for simple forms -->
                        <div v-if="!isComplexEntryType" class="mt-6 flex justify-start">
                            <button
                                type="button"
                                @click="deleteEntry"
                                class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600"
                            >
                                Delete {{ entry.entry_type?.name || 'Entry' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import DynamicEntryForm from '@/Components/entries/DynamicEntryForm.vue'
import { computed } from 'vue'

const props = defineProps({
    entry: {
        type: Object,
        required: true
    }
})

// Check if this is a complex entry type (has complex field types)
const isComplexEntryType = computed(() => {
    if (!props.entry?.entry_type?.field_config) {
        console.log('No entry_type or field_config found:', {
            entry: props.entry,
            entryType: props.entry?.entry_type,
            fieldConfig: props.entry?.entry_type?.field_config
        })
        return false
    }
    
    const hasComplex = props.entry.entry_type.field_config.some(field => 
        ['repeatable', 'image_collection', 'object', 'entry_relation'].includes(field.type)
    )
    
    console.log('isComplexEntryType check:', {
        fieldConfig: props.entry.entry_type.field_config,
        hasComplex
    })
    
    return hasComplex
})

// Extract content from JSON structure
const getContent = () => {
    if (!props.entry.content) return ''
    
    if (typeof props.entry.content === 'object') {
        return props.entry.content.statement || props.entry.content.content || ''
    }
    
    return props.entry.content
}

const form = useForm({
    title: props.entry.title,
    content: getContent(),
    status: props.entry.status
})

const handleDynamicSubmit = (dynamicForm) => {
    dynamicForm.patch(route('entries.update', props.entry.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect will be handled by the controller
        }
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
