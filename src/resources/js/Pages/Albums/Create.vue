<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    title: '',
    description: '',
    cover_image: null
});

const submit = () => {
    form.post(route('albums.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create Album" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Album</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input 
                                    type="text" 
                                    v-model="form.title"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                >
                                <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea 
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                ></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cover Image</label>
                                <input 
                                    type="file" 
                                    @input="form.cover_image = $event.target.files[0]"
                                    class="mt-1 block w-full"
                                    accept="image/*"
                                >
                                <div v-if="form.errors.cover_image" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.cover_image }}
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button 
                                    type="submit"
                                    class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700"
                                    :disabled="form.processing"
                                >
                                    Create Album
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 