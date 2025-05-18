<template>
    <Head title="Create Mosaic" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">
                    Create New Mosaic
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <form @submit.prevent="createMosaic" class="p-6">
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Title
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            />
                            <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description (Optional)
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <div class="flex justify-end gap-4">
                            <Link
                                :href="route('mosaics.index')"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                                :disabled="form.processing"
                            >
                                Create Mosaic
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    title: '',
    description: '',
});

const createMosaic = () => {
    form.post(route('mosaics.store'), {
        onSuccess: () => {
            // Redirect to edit page will be handled by the controller
        },
    });
};
</script>

<style scoped>
.header-title {
    @apply font-semibold text-xl text-gray-800 leading-tight;
}

.content-wrapper {
    @apply py-12;
}

.form-container {
    @apply max-w-7xl mx-auto sm:px-6 lg:px-8;
}

.form-card {
    @apply bg-white overflow-hidden shadow-sm rounded-lg;
}

.form-body {
    @apply p-6;
}

.form-layout {
    @apply space-y-6;
}

.form-group {
    @apply mb-4;
}

.form-label {
    @apply block text-sm font-medium text-gray-700;
}

.form-input {
    @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

.form-textarea {
    @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

.form-error {
    @apply text-red-500 text-sm mt-1;
}

.form-actions {
    @apply flex justify-end gap-4;
}

.cancel-button {
    @apply px-4 py-2 text-gray-700 hover:text-gray-900;
}

.submit-button {
    @apply bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded;
}
</style> 