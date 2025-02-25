<template>
    <Head title="Create Mosaic" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create New Mosaic
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Title
                                </label>
                                <input 
                                    type="text"
                                    v-model="form.title"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Description
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div class="flex justify-end gap-4">
                                <Link
                                    :href="route('mosaics.index')"
                                    class="px-4 py-2 text-gray-700 hover:text-gray-900"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                    :disabled="form.processing"
                                >
                                    Create Mosaic
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    title: '',
    description: '',
    theme_settings: {
        background_color: '#ffffff',
        text_color: '#000000',
    }
});

const submit = () => {
    form.post(route('mosaics.store'), {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script> 