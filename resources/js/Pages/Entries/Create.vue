<template>
    <Head :title="`Create ${entryType.name}`" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-semibold">Create New {{ entryType.name }}</h1>
                            <Link :href="route('entries.index', { type: entryType.slug })" class="text-gray-500 hover:text-gray-700">
                                ← Back to {{ entryType.name }}
                            </Link>
                        </div>

                        <!-- Use Dynamic Form for complex entry types, fallback to simple form for I AM -->
                        <DynamicEntryForm
                             v-if="isComplexEntryType"
                            :entryType="entryType"
                            submitText="Create"
                            @cancel="router.visit(route('entries.index', { type: entryType.slug }))"
                            @submit="handleDynamicSubmit"
                        />
                        
                        <!-- Simple form for I AM entries (backward compatibility) -->
                        <form v-else @submit.prevent="submitForm" class="space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Title
                                </label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    :placeholder="`Give your ${entryType.name} a title...`"
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
                                    :placeholder="`Enter ${entryType.name.toLowerCase()} content...`"
                                    required
                                ></textarea>
                                <div v-if="form.errors.content" class="text-red-600 text-sm mt-1">
                                    {{ form.errors.content }}
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <Link :href="route('entries.index', { type: entryType.slug })" 
                                      class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Creating...</span>
                                    <span v-else>Create {{ entryType.name }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DynamicEntryForm from '@/Components/entries/DynamicEntryForm.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    entryType: Object
});

// Check if this is a complex entry type (has complex field types)
const isComplexEntryType = computed(() => {
    return props.entryType.field_config.some(field => 
        ['repeatable', 'image_collection', 'object', 'entry_relation'].includes(field.type)
    )
});

const form = useForm({
    title: '',
    content: '',
    status: 'published',
    entry_type_id: ''
})

const submitForm = () => {
    // Set the entry_type_id before submitting
    form.entry_type_id = props.entryType.id;
    
    form.post(route('entries.store'));
}

const handleDynamicSubmit = (dynamicForm) => {
    dynamicForm.post(route('entries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect will be handled by the controller
        }
    });
};
</script>
