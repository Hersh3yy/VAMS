<template>
    <div>
        <Head :title="`Edit ${entry.title}`" />
        
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-semibold">Edit I AM</h2>
                            <Link :href="route('entries.show', entry.id)" class="text-gray-500 hover:text-gray-700">
                                ← Back to I AM
                            </Link>
                        </div>

                        <form @submit.prevent="form.patch(route('entries.update', entry.id))" class="space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Title
                                </label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="Give your I AM a title..."
                                    required
                                />
                                <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    I AM...
                                </label>
                                <textarea
                                    id="content"
                                    v-model="form.content"
                                    rows="6"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="I AM..."
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

                            <div class="flex justify-between">
                                <button
                                    type="button"
                                    @click="deleteEntry"
                                    class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600"
                                >
                                    Delete I AM
                                </button>
                                
                                <div class="flex space-x-3">
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
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    entry: {
        type: Object,
        required: true
    }
})

const form = useForm({
    title: props.entry.title,
    content: props.entry.content,
    status: props.entry.status
})

const deleteEntry = () => {
    if (confirm('Are you sure you want to delete this I AM? This action cannot be undone.')) {
        router.delete(route('entries.destroy', props.entry.id))
    }
}
</script>
